<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Faculty Only (Admin can be added later if needed, but request specified Faculty)
if ($_SESSION['user_type'] != 'faculty' && $_SESSION['user_type'] != 'admin') {
    header("Location: /research_management/public/login.php");
    exit();
}

$uid = $_SESSION['uid'];
$error = '';
$success = '';

// Fetch Projects where the user is involved
if ($_SESSION['user_type'] == 'admin') {
    $projects_query = "SELECT project_id, project_title FROM project";
    $stmt_projects = $conn->prepare($projects_query);
} else {
    $projects_query = "SELECT p.project_id, p.project_title 
                       FROM project p 
                       JOIN researcher_project rp ON p.project_id = rp.project_id 
                       WHERE rp.researcher_id = ?";
    $stmt_projects = $conn->prepare($projects_query);
    $stmt_projects->bind_param("s", $uid);
}

$stmt_projects->execute();
$projects_result = $stmt_projects->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $task_name = $_POST['task_name'];
    $task_status = $_POST['task_status'];
    $due_date = $_POST['due_date'];
    $task_description = $_POST['task_description'];

    try {
        // Generate Custom Task ID
        $id_query = "SELECT task_id FROM project_task 
                     ORDER BY CAST(SUBSTRING(task_id, 2) AS UNSIGNED) DESC LIMIT 1";
        $id_result = $conn->query($id_query);

        if ($id_result->num_rows > 0) {
            $row = $id_result->fetch_assoc();
            $last_id = $row['task_id'];
            $number = intval(substr($last_id, 1));
            $task_id = "t" . ($number + 1);
        } else {
            $task_id = "t1";
        }

        $stmt = $conn->prepare("INSERT INTO project_task (task_id, project_id, task_name, task_status, due_date, task_description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $task_id, $project_id, $task_name, $task_status, $due_date, $task_description);

        if ($stmt->execute()) {
            $success = "Task added successfully!";
        } else {
            throw new Exception("Error adding task: " . $stmt->error);
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
    <title>Add New Task - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Add New Task</h1>
                <p>Assign tasks to your projects and track progress.</p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>
            <?php if ($success)
                echo "<div class='success-message' style='color: #065f46; background: #d1fae5; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;'>$success</div>"; ?>

            <div class="form-container">
                <form action="add.php" method="POST" class="modern-form">
                    <div class="form-group">
                        <label>Project</label>
                        <select name="project_id" required>
                            <option value="">Select Project</option>
                            <?php
                            // Reset pointer just in case
                            $projects_result->data_seek(0);
                            while ($p = $projects_result->fetch_assoc()):
                                ?>
                                <option value="<?php echo htmlspecialchars($p['project_id']); ?>">
                                    <?php echo htmlspecialchars($p['project_title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Task Name</label>
                        <input type="text" name="task_name" placeholder="e.g. Conduct Literature Review" required>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Status</label>
                            <select name="task_status" required>
                                <option value="not_started">Not Started</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Due Date</label>
                            <input type="date" name="due_date" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="task_description" rows="4"
                            placeholder="Detail the task requirements..."></textarea>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-submit">Add Task</button>
                        <?php
                        $dashboard_url = ($_SESSION['user_type'] == 'faculty') ? '/research_management/public/dashboard_faculty.php' : '/research_management/public/dashboard.php';
                        ?>
                        <a href="<?php echo $dashboard_url; ?>"
                            style="margin-left: 20px; color: #6b7280; text-decoration: none; font-weight: 500;">Back to
                            Dashboard</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>