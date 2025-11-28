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
    <title>Faculty Dashboard</title>
</head>

<body>
    <h1>Welcome to the Faculty Dashboard</h1>


    <h2>Dashboard</h2>
    <ul>
        <li><strong>Ongoing Projects:</strong> <?php echo $ongoing_projects; ?></li>
        <li><strong>Total Publications:</strong> <?php echo $total_publications; ?></li>
        <li><strong>Total Funding Left:</strong> <?php echo number_format($total_funding_left, 2); ?> BDT</li>
    </ul>

    <h3>Quick Links</h3>
    <ul>
        <li><a href="/research_management/modules/researchers/list.php">Manage Researchers</a></li>
        <li><a href="/research_management/modules/projects/list.php">Projects</a></li>
        <li><a href="/research_management/modules/publications/list.php">Publications</a></li>
    </ul>

    <form action="logout.php" method="POST">
        <button type="Logout">Logout</button>
    </form>
</body>

</html>