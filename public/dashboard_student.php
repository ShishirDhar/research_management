<?php
// Include the database connection
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$uid = $_SESSION['uid'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - RMS</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Student Dashboard</h1>
                <p>Welcome! Here are your assigned tasks and projects.</p>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 class="section-title" style="margin-bottom: 0;">My Project Tasks</h2>
            </div>

            <div class="table-container">
                <table class="modern-table">
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
                                    <td>
                                        <strong
                                            style="color: #374151;"><?php echo htmlspecialchars($task['task_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['project_title']); ?></td>
                                    <td>
                                        <span style="
                                            padding: 2px 8px; 
                                            border-radius: 4px; 
                                            font-size: 0.8rem; 
                                            font-weight: 500;
                                            background: <?php
                                            $s = strtolower($task['task_status']);
                                            echo ($s == 'completed') ? '#ecfdf5' : (($s == 'in_progress') ? '#dbeafe' : '#f3f4f6');
                                            ?>;
                                            color: <?php
                                            echo ($s == 'completed') ? '#047857' : (($s == 'in_progress') ? '#1e40af' : '#374151');
                                            ?>;
                                        ">
                                            <?php echo htmlspecialchars($task['task_status']); ?>
                                        </span>
                                    </td>
                                    <td style="color: #6b7280; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($task['due_date']); ?></td>
                                    <td
                                        style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #6b7280;">
                                        <?php echo htmlspecialchars($task['task_description']); ?>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #6b7280;">No tasks assigned
                                    yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="action-grid" style="margin-top: 40px;">
                <a href="/research_management/modules/publications/list.php" class="action-card">
                    <h4>Browse Publications</h4>
                    <p style="font-size: 0.85rem; color: #6b7280; margin-top: 5px;">See latest research outputs</p>
                </a>
                <a href="/research_management/modules/projects/list.php" class="action-card">
                    <h4>View Projects</h4>
                    <p style="font-size: 0.85rem; color: #6b7280; margin-top: 5px;">Explore ongoing projects</p>
                </a>
            </div>

        </main>
    </div>
</body>

</html>