<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty Only
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
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
    $success = "Project updated successfully!";
}

// Fetch existing project
$stmt = $conn->prepare("SELECT * FROM project WHERE project_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("Project not found.");
}

// Fetch Researchers for Dropdown
$researchers_query = "SELECT researcher_id, f_name, l_name FROM researcher";
$researchers_result = $conn->query($researchers_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_title = $_POST['project_title'];
    $project_lead = $_POST['project_lead'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($start_date > $end_date) {
        $error = "Start date cannot be after end date.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE project SET project_title=?, project_lead=?, status=?, start_date=?, end_date=? WHERE project_id=?");
            $stmt->bind_param("ssssss", $project_title, $project_lead, $status, $start_date, $end_date, $id);

            if ($stmt->execute()) {
                header("Location: edit.php?id=$id&msg=updated");
                exit();
            } else {
                throw new Exception("Error updating project: " . $stmt->error);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project</title>
</head>

<body>
    <h1>Edit Project</h1>
    <?php if ($error)
        echo "<p style='color:red'>$error</p>"; ?>
    <?php if ($success)
        echo "<p style='color:green'>$success</p>"; ?>

    <form action="edit.php?id=<?php echo $id; ?>" method="POST">
        <label>Project Title:</label> <input type="text" name="project_title"
            value="<?php echo htmlspecialchars($project['project_title']); ?>" required><br><br>

        <label>Project Lead:</label>
        <select name="project_lead" required>
            <option value="">Select Researcher</option>
            <?php while ($r = $researchers_result->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($r['researcher_id']); ?>"
                    <?php if ($r['researcher_id'] == $project['project_lead']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name']); ?> (<?php echo $r['researcher_id']; ?>)
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Status:</label>
        <select name="status" required>
            <option value="ongoing" <?php if ($project['status'] == 'ongoing')
                echo 'selected'; ?>>Ongoing</option>
            <option value="completed" <?php if ($project['status'] == 'completed')
                echo 'selected'; ?>>Completed</option>
            <option value="published" <?php if ($project['status'] == 'published')
                echo 'selected'; ?>>Published</option>
        </select><br><br>

        <label>Start Date:</label> <input type="date" name="start_date"
            value="<?php echo htmlspecialchars($project['start_date']); ?>" required><br><br>
        <label>End Date:</label> <input type="date" name="end_date"
            value="<?php echo htmlspecialchars($project['end_date']); ?>" required><br><br>

        <button type="submit">Update Project</button>
    </form>
    <br>
    <a href="list.php">Back to List</a>
</body>

</html>