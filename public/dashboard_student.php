<?php
// Include the database connection
require_once __DIR__ . '/../config/db_connect.php'; // MySQLi connection file
require_once __DIR__ . '/../lib/auth.php'; // Authentication functions
require_login(); // Ensure the user is logged in


// Query for the number of ongoing projects
$ongoing_projects_query = "SELECT COUNT(*) as c FROM project WHERE status = 'ongoing'";
$ongoing_projects_result = $conn->query($ongoing_projects_query);
$ongoing_projects = $ongoing_projects_result->fetch_assoc()['c'];
// Query for the total number of publications
$total_publications_query = "SELECT COUNT(*) as d FROM publication";
$total_publications_result = $conn->query($total_publications_query);
$total_publications = $total_publications_result->fetch_assoc()['d'];


// Query for total funding left (amount_left)
$total_funding_left_query = "SELECT SUM(amount_left) as s FROM funding";
$total_funding_left_result = $conn->query($total_funding_left_query);
$total_funding_left = $total_funding_left_result->fetch_assoc()['s'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
</head>

<body>
    <h1>Welcome to the Student Dashboard</h1>


    <h2>Dashboard</h2>
    <ul>
        <li><strong>Ongoing Projects:</strong> <?php echo $ongoing_projects; ?></li>
        <li><strong>Total Publications:</strong> <?php echo $total_publications; ?></li>
        <li><strong>Total Funding Left:</strong> <?php echo number_format($total_funding_left, 2); ?> BDT</li>
    </ul>

    <h3>My Project Tasks</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Task Name</th>
                <th>Project</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Description</th>
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
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="5">No tasks found.</td>
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