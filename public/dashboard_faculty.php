<?php
// Include the database connection
require_once __DIR__ . '/../config/db_connect.php'; // MySQLi connection file
require_once __DIR__ . '/../lib/auth.php'; // Authentication functions
require_login(); // Ensure the user is logged in



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
</head>

<body>
    <h1>Welcome to the Faculty Dashboard</h1>

    <h3>Project Tasks</h3>
    <a href="/research_management/modules/tasks/add.php">Add New Task</a>
    <br><br>
    <table border="1">
        <thead>
            <tr>
                <th>Task Name</th>
                <th>Project</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $uid = $_SESSION['uid'];
            $tasks_query = "SELECT pt.*, p.project_title 
                            FROM project_task pt 
                            JOIN researcher_project rp ON pt.project_id = rp.project_id 
                            JOIN project p ON pt.project_id = p.project_id
                            WHERE rp.researcher_id = ?";
            $stmt_tasks = $conn->prepare($tasks_query);
            $stmt_tasks->bind_param("s", $uid);
            $stmt_tasks->execute();
            $tasks_result = $stmt_tasks->get_result();

            if ($tasks_result->num_rows > 0):
                while ($task = $tasks_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                        <td><?php echo htmlspecialchars($task['project_title']); ?></td>
                        <td><?php echo htmlspecialchars($task['task_status']); ?></td>
                        <td><?php echo htmlspecialchars($task['due_date']); ?></td>
                        <td><?php echo htmlspecialchars($task['task_description']); ?></td>
                        <td>
                            <a href="/research_management/modules/tasks/edit.php?id=<?php echo $task['task_id']; ?>">Edit</a> |
                            <a href="/research_management/modules/tasks/delete.php?id=<?php echo $task['task_id']; ?>"
                                onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="6">No tasks found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Quick Links</h3>
    <ul>
        <li><a href="/research_management/public/profile.php">My Profile</a></li>
        <li><a href="/research_management/modules/researchers/list.php">Researchers</a></li>
        <li><a href="/research_management/modules/projects/list.php">Projects</a></li>
        <li><a href="/research_management/modules/publications/list.php">Publications</a></li>
    </ul>

    <form action="logout.php" method="POST">
        <button type="Logout">Logout</button>
    </form>
</body>

</html>