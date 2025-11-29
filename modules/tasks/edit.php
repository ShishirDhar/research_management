<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: /research_management/public/dashboard_faculty.php");
    exit();
}

$uid = $_SESSION['uid'];
$error = '';
$success = '';

// Fetch Task and Verify Access
// We join with researcher_project to ensure the user is part of the project (unless Admin)
if ($_SESSION['user_type'] == 'admin') {
    $query = "SELECT * FROM project_task WHERE task_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $id);
} else {
    $query = "SELECT pt.* FROM project_task pt 
              JOIN researcher_project rp ON pt.project_id = rp.project_id 
              WHERE pt.task_id = ? AND rp.researcher_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $id, $uid);
}

$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    die("Task not found or access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = $_POST['task_name'];
    $task_status = $_POST['task_status'];
    $due_date = $_POST['due_date'];
    $task_description = $_POST['task_description'];

    try {
        $update_stmt = $conn->prepare("UPDATE project_task SET task_name=?, task_status=?, due_date=?, task_description=? WHERE task_id=?");
        $update_stmt->bind_param("sssss", $task_name, $task_status, $due_date, $task_description, $id);

        if ($update_stmt->execute()) {
            $success = "Task updated successfully!";
            // Refresh data
            $stmt->execute();
            $task = $stmt->get_result()->fetch_assoc();
        } else {
            throw new Exception("Error updating task: " . $update_stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
</head>

<body>
    <h1>Edit Task</h1>
    <?php if ($error)
        echo "<p style='color:red'>$error</p>"; ?>
    <?php if ($success)
        echo "<p style='color:green'>$success</p>"; ?>

    <form action="edit.php?id=<?php echo $id; ?>" method="POST">
        <label>Task Name:</label> <input type="text" name="task_name"
            value="<?php echo htmlspecialchars($task['task_name']); ?>" required><br><br>

        <label>Status:</label>
        <select name="task_status" required>
            <option value="not_started" <?php if ($task['task_status'] == 'not_started')
                echo 'selected'; ?>>Not Started
            </option>
            <option value="in_progress" <?php if ($task['task_status'] == 'in_progress')
                echo 'selected'; ?>>In Progress
            </option>
            <option value="completed" <?php if ($task['task_status'] == 'completed')
                echo 'selected'; ?>>Completed
            </option>
        </select><br><br>

        <label>Due Date:</label> <input type="date" name="due_date"
            value="<?php echo htmlspecialchars($task['due_date']); ?>" required><br><br>

        <label>Description:</label><br>
        <textarea name="task_description" rows="4"
            cols="50"><?php echo htmlspecialchars($task['task_description']); ?></textarea><br><br>

        <button type="submit">Update Task</button>
    </form>
    <br>
    <?php
    $dashboard_url = ($_SESSION['user_type'] == 'faculty') ? '/research_management/public/dashboard_faculty.php' : '/research_management/public/dashboard.php';
    ?>
    <a href="<?php echo $dashboard_url; ?>">Back to Dashboard</a>
</body>

</html>