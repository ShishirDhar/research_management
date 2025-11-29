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
    <title>Add New Task</title>
</head>

<body>
    <h1>Add New Task</h1>
    <?php if ($error)
        echo "<p style='color:red'>$error</p>"; ?>
    <?php if ($success)
        echo "<p style='color:green'>$success</p>"; ?>

    <form action="add.php" method="POST">
        <label>Project:</label>
        <select name="project_id" required>
            <option value="">Select Project</option>
            <?php while ($p = $projects_result->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($p['project_id']); ?>">
                    <?php echo htmlspecialchars($p['project_title']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Task Name:</label> <input type="text" name="task_name" required><br><br>

        <label>Status:</label>
        <select name="task_status" required>
            <option value="not_started">Not Started</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
        </select><br><br>

        <label>Due Date:</label> <input type="date" name="due_date" required><br><br>

        <label>Description:</label><br>
        <textarea name="task_description" rows="4" cols="50"></textarea><br><br>

        <button type="submit">Add Task</button>
    </form>
    <br>
    <?php
    $dashboard_url = ($_SESSION['user_type'] == 'faculty') ? '/research_management/public/dashboard_faculty.php' : '/research_management/public/dashboard.php';
    ?>
    <a href="<?php echo $dashboard_url; ?>">Back to Dashboard</a>
</body>

</html>