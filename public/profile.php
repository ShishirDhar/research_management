<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../lib/auth.php';

require_login();

$uid = $_SESSION['uid'];

// Fetch Researcher Info
$query = "SELECT r.f_name, r.l_name, rp.biography, rp.research_interests 
          FROM researcher r 
          LEFT JOIN researcher_profile rp ON r.researcher_id = rp.researcher_id 
          WHERE r.researcher_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
</head>

<body>
    <h1>My Profile</h1>

    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['f_name'] . ' ' . $user['l_name']); ?></p>

    <h3>Biography</h3>
    <p><?php echo nl2br(htmlspecialchars($user['biography'] ?? 'No biography added yet.')); ?></p>

    <h3>Research Interests</h3>
    <p><?php echo nl2br(htmlspecialchars($user['research_interests'] ?? 'No research interests added yet.')); ?></p>

    <a href="profile_edit.php">Edit Profile</a>
    <br><br>
    <?php
    $dashboard_url = ($_SESSION['user_type'] == 'faculty') ? '/research_management/public/dashboard_faculty.php' : '/research_management/public/dashboard_student.php';
    if ($_SESSION['user_type'] == 'admin')
        $dashboard_url = '/research_management/public/dashboard.php';
    ?>
    <a href="<?php echo $dashboard_url; ?>">Back to Dashboard</a>
</body>

</html>