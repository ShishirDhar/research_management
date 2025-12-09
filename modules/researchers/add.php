<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Only Admin
if ($_SESSION['user_type'] != 'admin') {
    header("Location: list.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f_name = $_POST['f_name'];
    $l_name = $_POST['l_name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // Plain text as per current system design
    $department = $_POST['department'];
    $type = $_POST['type'];

    $contact_no = $_POST['contact_no'];
    $biography = $_POST['biography'];
    $research_interests = $_POST['research_interests'];

    // Start Transaction
    $conn->begin_transaction();

    try {
        // Generate Custom ID
        // Logic: Find the max ID number and increment it.
        // We extract the number after 'r' and cast to integer for sorting.
        $id_query = "SELECT researcher_id FROM researcher 
                     ORDER BY CAST(SUBSTRING(researcher_id, 2) AS UNSIGNED) DESC LIMIT 1";
        $id_result = $conn->query($id_query);

        if ($id_result->num_rows > 0) {
            $row = $id_result->fetch_assoc();
            $last_id = $row['researcher_id'];
            // Extract number (remove 'r')
            $number = intval(substr($last_id, 1));
            $researcher_id = "r" . ($number + 1);
        } else {
            $researcher_id = "r1";
        }

        // Insert into researcher table
        $stmt = $conn->prepare("INSERT INTO researcher (researcher_id, f_name, l_name, email, password, department) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $researcher_id, $f_name, $l_name, $email, $password, $department);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting researcher: " . $stmt->error);
        }

        // Insert into specific type table
        if ($type == 'student') {
            $degree_program = $_POST['degree_program'];
            $year_level = $_POST['year_level'];

            $stmt_student = $conn->prepare("INSERT INTO student (researcher_id, degree_program, year_level) VALUES (?, ?, ?)");
            $stmt_student->bind_param("sss", $researcher_id, $degree_program, $year_level);

            if (!$stmt_student->execute()) {
                throw new Exception("Error inserting student details: " . $stmt_student->error);
            }
        } else if ($type == 'faculty') {
            $experience = $_POST['experience'];
            $initials = $_POST['initials'];

            $stmt_faculty = $conn->prepare("INSERT INTO faculty (researcher_id, experience, initials) VALUES (?, ?, ?)");
            $stmt_faculty->bind_param("sis", $researcher_id, $experience, $initials);

            if (!$stmt_faculty->execute()) {
                throw new Exception("Error inserting faculty details: " . $stmt_faculty->error);
            }
        }

        // Insert into researcher_contact
        if (!empty($contact_no)) {
            $stmt_contact = $conn->prepare("INSERT INTO researcher_contact (researcher_id, contact_no) VALUES (?, ?)");
            $stmt_contact->bind_param("ss", $researcher_id, $contact_no);
            if (!$stmt_contact->execute()) {
                throw new Exception("Error inserting contact details: " . $stmt_contact->error);
            }
        }

        // Insert into researcher_profile
        if (!empty($biography) || !empty($research_interests)) {
            $stmt_profile = $conn->prepare("INSERT INTO researcher_profile (researcher_id, biography, research_interests) VALUES (?, ?, ?)");
            $stmt_profile->bind_param("sss", $researcher_id, $biography, $research_interests);
            if (!$stmt_profile->execute()) {
                throw new Exception("Error inserting profile details: " . $stmt_profile->error);
            }
        }

        $conn->commit();
        $success = "Researcher added successfully!";

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
    <title>Add Researcher - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
    <script>
        function toggleFields() {
            var type = document.getElementById('type').value;
            var studentFields = document.getElementById('student_fields');
            var facultyFields = document.getElementById('faculty_fields');

            if (type === 'student') {
                studentFields.style.display = 'block';
                facultyFields.style.display = 'none';
                document.getElementById('degree_program').required = true;
                document.getElementById('year_level').required = true;
                document.getElementById('experience').required = false;
            } else {
                studentFields.style.display = 'none';
                facultyFields.style.display = 'block';
                document.getElementById('degree_program').required = false;
                document.getElementById('year_level').required = false;
                document.getElementById('experience').required = true;
            }
        }
    </script>
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Add New Researcher</h1>
                <p>Create a new researcher profile in the system.</p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>
            <?php if ($success)
                echo "<div class='success-message' style='color: green; padding: 10px; background: #ecfdf5; border-radius: 6px; margin-bottom: 20px;'>$success</div>"; ?>

            <div class="form-container">
                <form action="add.php" method="POST" class="modern-form">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="f_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="l_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" required>
                    </div>

                    <div class="form-group">
                        <label>Contact No</label>
                        <input type="text" name="contact_no">
                    </div>
                    <div class="form-group">
                        <label>Biography</label>
                        <textarea name="biography" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Research Interests</label>
                        <input type="text" name="research_interests">
                    </div>

                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" id="type" onchange="toggleFields()" required>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty</option>
                        </select>
                    </div>

                    <div id="student_fields">
                        <div class="form-group">
                            <label>Degree Program</label>
                            <input type="text" name="degree_program" id="degree_program" required>
                        </div>
                        <div class="form-group">
                            <label>Year Level</label>
                            <input type="text" name="year_level" id="year_level" required>
                        </div>
                    </div>

                    <div id="faculty_fields" style="display:none;">
                        <div class="form-group">
                            <label>Experience (Years)</label>
                            <input type="number" name="experience" id="experience">
                        </div>
                        <div class="form-group">
                            <label>Initials</label>
                            <input type="text" name="initials" id="initials">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Add Researcher</button>
                    <a href="list.php" style="margin-left: 15px; color: #6b7280; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>

</html>