<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty Only
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

// Fetch all projects
$query = "SELECT * FROM project";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects</title>
</head>

<body>
    <h1>Projects</h1>
    <a href="add.php">Add New Project</a>
    <br><br>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Lead</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['project_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['project_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['project_lead']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $row['project_id']; ?>">Edit</a> |
                            <a href="delete.php?id=<?php echo $row['project_id']; ?>"
                                onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No projects found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br>
    <?php
    $dashboard_url = '/research_management/public/dashboard.php';
    if ($_SESSION['user_type'] == 'faculty') {
        $dashboard_url = '/research_management/public/dashboard_faculty.php';
    }
    ?>
    <a href="<?php echo $dashboard_url; ?>">Back to Dashboard</a>
</body>

</html>