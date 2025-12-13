<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

// Fetch all funding records with aggregated project titles
$query = "
    SELECT 
        f.funding_id, 
        f.agency_name, 
        f.total_grant,
        GROUP_CONCAT(p.project_title SEPARATOR ', ') as linked_projects
    FROM funding f
    LEFT JOIN project_funding pf ON f.funding_id = pf.funding_id
    LEFT JOIN project p ON pf.project_id = p.project_id
    GROUP BY f.funding_id
    ORDER BY f.agency_name ASC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Funding - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
    <style>
        .action-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
            margin-right: 15px;
        }

        .action-link.delete {
            color: #ef4444;
        }

        .action-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h1>Project Funding</h1>
                    <p>Manage funding agencies and grants.</p>
                </div>
                <a href="add.php" class="btn-primary"
                    style="background: #4f46e5; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500;">+
                    Add New Funding</a>
            </div>

            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Agency Name</th>
                            <th>Total Grant</th>
                            <th>Linked Projects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="view.php?id=<?php echo $row['funding_id']; ?>"
                                            style="color: #111827; text-decoration: none; font-weight: 600;">
                                            <?php echo htmlspecialchars($row['agency_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span
                                            style="font-family: monospace; background: #ecfdf5; color: #065f46; padding: 4px 8px; border-radius: 4px;">
                                            ৳<?php echo number_format($row['total_grant']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['linked_projects']): ?>
                                            <?php echo htmlspecialchars($row['linked_projects']); ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-style: italic;">No projects linked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $row['funding_id']; ?>" class="action-link">Edit</a>
                                        <a href="delete.php?id=<?php echo $row['funding_id']; ?>" class="action-link delete"
                                            onclick="return confirm('Are you sure you want to delete this funding source?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: #6b7280;">
                                    No funding records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>