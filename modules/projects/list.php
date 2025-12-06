<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Fetch projects based on user role
if ($_SESSION['user_type'] == 'admin') {
    $query = "SELECT * FROM project";
    $result = $conn->query($query);
} else {
    // Faculty and Students see projects they lead OR are members of
    $uid = $_SESSION['uid'];
    $query = "SELECT DISTINCT p.* 
              FROM project p 
              LEFT JOIN researcher_project rp ON p.project_id = rp.project_id 
              WHERE p.project_lead = ? OR rp.researcher_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $uid, $uid);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Projects</h1>
                <p>Manage and track research projects.</p>
            </div>

            <?php if ($_SESSION['user_type'] != 'student'): ?>
                <a href="add.php" class="btn-primary">Add New Project</a>
            <?php endif; ?>

            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Lead</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Team Members</th>
                            <?php if ($_SESSION['user_type'] != 'student'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['project_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['project_title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['project_lead']); ?></td>
                                    <td>
                                        <span style="
                                            padding: 4px 8px; 
                                            border-radius: 4px; 
                                            font-size: 0.85rem; 
                                            background: <?php echo ($row['status'] == 'ongoing') ? '#dbeafe' : '#f3f4f6'; ?>;
                                            color: <?php echo ($row['status'] == 'ongoing') ? '#1e40af' : '#374151'; ?>;
                                        ">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                                    <td>
                                        <?php
                                        $pid = $row['project_id'];
                                        $tm_query = "SELECT r.f_name, r.l_name, rp.role FROM researcher_project rp JOIN researcher r ON rp.researcher_id = r.researcher_id WHERE rp.project_id = ?";
                                        $stmt_tm = $conn->prepare($tm_query);
                                        $stmt_tm->bind_param("s", $pid);
                                        $stmt_tm->execute();
                                        $res_tm = $stmt_tm->get_result();
                                        $members = [];
                                        while ($tm = $res_tm->fetch_assoc()) {
                                            $members[] = htmlspecialchars($tm['f_name'] . ' ' . $tm['l_name']);
                                        }
                                        echo implode(', ', $members);
                                        ?>
                                    </td>
                                    <?php if ($_SESSION['user_type'] != 'student'): ?>
                                        <td>
                                            <a href="edit.php?id=<?php echo $row['project_id']; ?>">Edit</a>
                                            <a href="delete.php?id=<?php echo $row['project_id']; ?>"
                                                onclick="return confirm('Are you sure?')" style="color: #ef4444;">Delete</a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($_SESSION['user_type'] != 'student') ? 8 : 7; ?>"
                                    style="text-align: center; color: #6b7280;">No projects found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>