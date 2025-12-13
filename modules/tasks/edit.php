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
    <title>Edit Task - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Edit Task</h1>
                <p>Update task details and status.</p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>
            <?php if ($success)
                echo "<div class='success-message' style='color: green; padding: 10px; background: #ecfdf5; border-radius: 6px; margin-bottom: 20px;'>$success</div>"; ?>

            <div class="form-container">
                <form action="edit.php?id=<?php echo $id; ?>" method="POST" class="modern-form">
                    <div class="form-group">
                        <label>Task Name</label>
                        <input type="text" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>"
                            required>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Status</label>
                            <select name="task_status" required>
                                <option value="not_started" <?php if ($task['task_status'] == 'not_started')
                                    echo 'selected'; ?>>Not Started</option>
                                <option value="in_progress" <?php if ($task['task_status'] == 'in_progress')
                                    echo 'selected'; ?>>In Progress</option>
                                <option value="completed" <?php if ($task['task_status'] == 'completed')
                                    echo 'selected'; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Due Date</label>
                            <input type="date" name="due_date"
                                value="<?php echo htmlspecialchars($task['due_date']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="task_description" rows="4"
                            required><?php echo htmlspecialchars($task['task_description']); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Update Task</button>
                    <?php
                    $dashboard_url = ($_SESSION['user_type'] == 'faculty') ? '/research_management/public/dashboard_faculty.php' : '/research_management/public/dashboard.php';
                    ?>
                    <a href="<?php echo $dashboard_url; ?>"
                        style="margin-left: 15px; color: #6b7280; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>

</html>