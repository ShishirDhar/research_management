<?php
// Include the database connection
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

// Query for the number of ongoing projects
$ongoing_projects_query = "SELECT COUNT(*) as c FROM project WHERE status = 'ongoing'";
$ongoing_projects_result = $conn->query($ongoing_projects_query);
$ongoing_projects = $ongoing_projects_result->fetch_assoc()['c'];

// Query for the total number of publications
$total_publications_query = "SELECT COUNT(*) as d FROM publication";
$total_publications_result = $conn->query($total_publications_query);
$total_publications = $total_publications_result->fetch_assoc()['d'];

// Query for total funding left (amount_left) REMOVED as per user request
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Research Management</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Dashboard Overview</h1>
                <p>Welcome back, Admin. Here's what's happening.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <h3>Ongoing Projects</h3>
                    <div class="value"><?php echo $ongoing_projects; ?></div>
                </div>
                <div class="stat-card purple">
                    <h3>Publications</h3>
                    <div class="value"><?php echo $total_publications; ?></div>
                </div>
            </div>

            <h2 class="section-title">Quick Actions</h2>
            <div class="action-grid">
                <a href="/research_management/modules/researchers/add.php" class="action-card">
                    <h4>Add Researcher</h4>
                </a>
                <a href="/research_management/modules/projects/add.php" class="action-card">
                    <h4>Create Project</h4>
                </a>

            </div>
        </main>
    </div>
</body>

</html>