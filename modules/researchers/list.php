<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Fetch all researchers with profile info
$query = "SELECT r.*, rp.biography, rp.research_interests 
          FROM researcher r 
          LEFT JOIN researcher_profile rp ON r.researcher_id = rp.researcher_id";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Researchers - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Researchers</h1>
                    <p>Directory of all researchers in the department.</p>
                </div>
                <?php if ($_SESSION['user_type'] == 'admin'): ?>
                    <a href="add.php" class="btn-primary">+ Add New Researcher</a>
                <?php endif; ?>
            </div>

            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Biography</th>
                            <th>Research Interests</th>
                            <?php if ($_SESSION['user_type'] == 'admin'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span
                                            style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; color: #374151;">
                                            <?php echo $row['researcher_id']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong
                                            style="color: #111827;"><?php echo htmlspecialchars($row['f_name'] . ' ' . $row['l_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td
                                        style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #6b7280;">
                                        <?php echo htmlspecialchars($row['biography'] ?? '-'); ?>
                                    </td>
                                    <td
                                        style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #6b7280;">
                                        <?php echo htmlspecialchars($row['research_interests'] ?? '-'); ?>
                                    </td>
                                    <?php if ($_SESSION['user_type'] == 'admin'): ?>
                                        <td>
                                            <a href="edit.php?id=<?php echo $row['researcher_id']; ?>"
                                                style="color: #4f46e5; font-weight: 500; margin-right: 10px;">Edit</a>
                                            <a href="delete.php?id=<?php echo $row['researcher_id']; ?>"
                                                onclick="return confirm('Are you sure?')"
                                                style="color: #ef4444; font-weight: 500;">Delete</a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($_SESSION['user_type'] == 'admin') ? 7 : 6; ?>"
                                    style="text-align: center; padding: 40px; color: #6b7280;">
                                    No researchers found.
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