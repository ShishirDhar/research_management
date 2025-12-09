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
    <title>Faculty Dashboard - RMS</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Faculty Dashboard</h1>
                <p>Overview of your research activities and tasks.</p>
            </div>

            <!-- Quick Actions Grid -->
            <div class="action-grid" style="margin-bottom: 30px;">
                <a href="/research_management/modules/projects/add.php" class="action-card">
                    <h4>Create Project</h4>
                    <p style="font-size: 0.85rem; color: #6b7280; margin-top: 5px;">Start a new research initiative</p>
                </a>
                <a href="/research_management/modules/tasks/add.php" class="action-card">
                    <h4>Add New Task</h4>
                    <p style="font-size: 0.85rem; color: #6b7280; margin-top: 5px;">Assign work to your projects</p>
                </a>
                <a href="/research_management/modules/publications/list.php" class="action-card">
                    <h4>My Publications</h4>
                    <p style="font-size: 0.85rem; color: #6b7280; margin-top: 5px;">View your research output</p>
                </a>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 class="section-title" style="margin-bottom: 0;">Project Tasks</h2>
                <a href="/research_management/modules/tasks/add.php" class="btn-primary"
                    style="font-size: 0.9rem; padding: 6px 14px;">+ Add Task</a>
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
                            <th>Actions</th>
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
                                        <?php echo htmlspecialchars($task['due_date']); ?>
                                    </td>
                                    <td
                                        style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #6b7280;">
                                        <?php echo htmlspecialchars($task['task_description']); ?>
                                    </td>
                                    <td>
                                        <a href="/research_management/modules/tasks/edit.php?id=<?php echo $task['task_id']; ?>"
                                            style="color: #4f46e5; margin-right: 10px; font-weight: 500;">Edit</a>
                                        <a href="/research_management/modules/tasks/delete.php?id=<?php echo $task['task_id']; ?>"
                                            onclick="return confirm('Are you sure?')"
                                            style="color: #ef4444; font-weight: 500;">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #6b7280;">No tasks found.
                                    Get started by adding one!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>