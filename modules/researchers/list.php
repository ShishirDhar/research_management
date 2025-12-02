<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Allow Admin, Faculty, and Student (Read-Only for non-Admin)
// Previously redirected students, now allowing them.

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
    <title>Researchers</title>
</head>

<body>
    <h1>Researchers</h1>
    <?php if ($_SESSION['user_type'] == 'admin'): ?>
        <a href="add.php">Add New Researcher</a>
        <br><br>
    <?php endif; ?>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
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
                        <td><?php echo $row['researcher_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['f_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['l_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td><?php echo htmlspecialchars($row['biography'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['research_interests'] ?? ''); ?></td>
                        <?php if ($_SESSION['user_type'] == 'admin'): ?>
                            <td>
                                <a href="edit.php?id=<?php echo $row['researcher_id']; ?>">Edit</a> |
                                <a href="delete.php?id=<?php echo $row['researcher_id']; ?>"
                                    onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo ($_SESSION['user_type'] == 'admin') ? 8 : 7; ?>">No researchers found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br>
    <?php
    $dashboard_url = '/research_management/public/dashboard.php';
    if ($_SESSION['user_type'] == 'faculty') {
        $dashboard_url = '/research_management/public/dashboard_faculty.php';
    } elseif ($_SESSION['user_type'] == 'student') {
        $dashboard_url = '/research_management/public/dashboard_student.php';
    }
    ?>
    <a href="<?php echo $dashboard_url; ?>">Back to Dashboard</a>
</body>

</html>