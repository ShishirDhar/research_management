<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control
if ($_SESSION['user_type'] != 'admin') {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list.php");
    exit();
}

$error = '';
$success = '';
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $success = "Researcher updated successfully!";
}

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM researcher WHERE researcher_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$researcher = $stmt->get_result()->fetch_assoc();

if (!$researcher) {
    die("Researcher not found.");
}

// Determine type and fetch specific data
$type = '';
$student_data = null;
$faculty_data = null;

$stmt_student = $conn->prepare("SELECT * FROM student WHERE researcher_id = ?");
$stmt_student->bind_param("s", $id);
$stmt_student->execute();
$student_data = $stmt_student->get_result()->fetch_assoc();

if ($student_data) {
    $type = 'student';
} else {
    $stmt_faculty = $conn->prepare("SELECT * FROM faculty WHERE researcher_id = ?");
    $stmt_faculty->bind_param("s", $id);
    $stmt_faculty->execute();
    $faculty_data = $stmt_faculty->get_result()->fetch_assoc();
    if ($faculty_data) {
        $type = 'faculty';
    }
}

// Fetch contact info
$contact_data = null;
$stmt_contact = $conn->prepare("SELECT * FROM researcher_contact WHERE researcher_id = ?");
$stmt_contact->bind_param("s", $id);
$stmt_contact->execute();
$contact_data = $stmt_contact->get_result()->fetch_assoc();

// Fetch profile info
$profile_data = null;
$stmt_profile = $conn->prepare("SELECT * FROM researcher_profile WHERE researcher_id = ?");
$stmt_profile->bind_param("s", $id);
$stmt_profile->execute();
$profile_data = $stmt_profile->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f_name = $_POST['f_name'];
    $l_name = $_POST['l_name'];
    $email = $_POST['email'];
    $department = $_POST['department'];

    $contact_no = $_POST['contact_no'];
    $biography = $_POST['biography'];
    $research_interests = $_POST['research_interests'];
    // Password update is optional, logic omitted for simplicity unless requested

    $conn->begin_transaction();

    try {
        // Update researcher table
        $stmt = $conn->prepare("UPDATE researcher SET f_name=?, l_name=?, email=?, department=? WHERE researcher_id=?");
        $stmt->bind_param("sssss", $f_name, $l_name, $email, $department, $id);
        $stmt->execute();

        // Update specific table
        if ($type == 'student') {
            $degree_program = $_POST['degree_program'];
            $year_level = $_POST['year_level'];
            $stmt_s = $conn->prepare("UPDATE student SET degree_program=?, year_level=? WHERE researcher_id=?");
            $stmt_s->bind_param("sss", $degree_program, $year_level, $id);
            $stmt_s->execute();
        } else if ($type == 'faculty') {
            $experience = $_POST['experience'];
            $initials = $_POST['initials'];
            $stmt_f = $conn->prepare("UPDATE faculty SET experience=?, initials=? WHERE researcher_id=?");
            $stmt_f->bind_param("iss", $experience, $initials, $id);
            $stmt_f->execute();
        }

        // Update Contact
        // Strategy: Delete existing and insert new to avoid primary key conflicts
        $stmt_del_contact = $conn->prepare("DELETE FROM researcher_contact WHERE researcher_id = ?");
        $stmt_del_contact->bind_param("s", $id);
        $stmt_del_contact->execute();

        if (!empty($contact_no)) {
            $stmt_c = $conn->prepare("INSERT INTO researcher_contact (researcher_id, contact_no) VALUES (?, ?)");
            $stmt_c->bind_param("ss", $id, $contact_no);
            $stmt_c->execute();
        }

        // Update Profile
        $stmt_check_profile = $conn->prepare("SELECT * FROM researcher_profile WHERE researcher_id = ?");
        $stmt_check_profile->bind_param("s", $id);
        $stmt_check_profile->execute();
        if ($stmt_check_profile->get_result()->num_rows > 0) {
            $stmt_p = $conn->prepare("UPDATE researcher_profile SET biography=?, research_interests=? WHERE researcher_id=?");
            $stmt_p->bind_param("sss", $biography, $research_interests, $id);
            $stmt_p->execute();
        } else if (!empty($biography) || !empty($research_interests)) {
            $stmt_p = $conn->prepare("INSERT INTO researcher_profile (researcher_id, biography, research_interests) VALUES (?, ?, ?)");
            $stmt_p->bind_param("sss", $id, $biography, $research_interests);
            $stmt_p->execute();
        }

        $conn->commit();
        header("Location: edit.php?id=$id&msg=updated");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Researcher</title>
</head>

<body>
    <h1>Edit Researcher</h1>
    <?php if ($error)
        echo "<p style='color:red'>$error</p>"; ?>
    <?php if ($success)
        echo "<p style='color:green'>$success</p>"; ?>

    <form action="edit.php?id=<?php echo $id; ?>" method="POST">
        <label>First Name:</label> <input type="text" name="f_name"
            value="<?php echo htmlspecialchars($researcher['f_name']); ?>" required><br><br>
        <label>Last Name:</label> <input type="text" name="l_name"
            value="<?php echo htmlspecialchars($researcher['l_name']); ?>" required><br><br>
        <label>Email:</label> <input type="email" name="email"
            value="<?php echo htmlspecialchars($researcher['email']); ?>" required><br><br>
        <label>Department:</label> <input type="text" name="department"
            value="<?php echo htmlspecialchars($researcher['department']); ?>" required><br><br>

        <label>Contact No:</label> <input type="text" name="contact_no"
            value="<?php echo htmlspecialchars($contact_data['contact_no'] ?? ''); ?>"><br><br>
        <label>Biography:</label> <textarea
            name="biography"><?php echo htmlspecialchars($profile_data['biography'] ?? ''); ?></textarea><br><br>
        <label>Research Interests:</label> <input type="text" name="research_interests"
            value="<?php echo htmlspecialchars($profile_data['research_interests'] ?? ''); ?>"><br><br>

        <p><strong>Type:</strong> <?php echo ucfirst($type); ?></p>

        <?php if ($type == 'student'): ?>
            <label>Degree Program:</label> <input type="text" name="degree_program"
                value="<?php echo htmlspecialchars($student_data['degree_program']); ?>" required><br><br>
            <label>Year Level:</label> <input type="text" name="year_level"
                value="<?php echo htmlspecialchars($student_data['year_level']); ?>" required><br><br>
        <?php elseif ($type == 'faculty'): ?>
            <label>Experience (Years):</label> <input type="number" name="experience"
                value="<?php echo htmlspecialchars($faculty_data['experience']); ?>" required><br><br>
            <label>Initials:</label> <input type="text" name="initials"
                value="<?php echo htmlspecialchars($faculty_data['initials'] ?? ''); ?>"><br><br>
        <?php endif; ?>

        <button type="submit">Update Researcher</button>
    </form>
    <br>
    <a href="list.php">Back to List</a>
</body>

</html>