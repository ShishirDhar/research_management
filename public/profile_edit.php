<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../lib/auth.php';

require_login();

$uid = $_SESSION['uid'];
$error = '';
$success = '';

// Fetch Current Info
$query = "SELECT biography, research_interests FROM researcher_profile WHERE researcher_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $biography = $_POST['biography'];
    $research_interests = $_POST['research_interests'];

    // Check if profile exists
    if ($profile) {
        // Update
        $update_stmt = $conn->prepare("UPDATE researcher_profile SET biography = ?, research_interests = ? WHERE researcher_id = ?");
        $update_stmt->bind_param("sss", $biography, $research_interests, $uid);
    } else {
        // Insert (if for some reason it doesn't exist, though it should be created on researcher add)
        $update_stmt = $conn->prepare("INSERT INTO researcher_profile (researcher_id, biography, research_interests) VALUES (?, ?, ?)");
        $update_stmt->bind_param("sss", $uid, $biography, $research_interests);
    }

    if ($update_stmt->execute()) {
        $success = "Profile updated successfully!";
        // Refresh data
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
</head>

<body>
    <h1>Edit Profile</h1>
    <?php if ($error)
        echo "<p style='color:red'>$error</p>"; ?>
    <?php if ($success)
        echo "<p style='color:green'>$success</p>"; ?>

    <form action="profile_edit.php" method="POST">
        <label>Biography:</label><br>
        <textarea name="biography" rows="5"
            cols="50"><?php echo htmlspecialchars($profile['biography'] ?? ''); ?></textarea><br><br>

        <label>Research Interests:</label><br>
        <textarea name="research_interests" rows="5"
            cols="50"><?php echo htmlspecialchars($profile['research_interests'] ?? ''); ?></textarea><br><br>

        <button type="submit">Update Profile</button>
    </form>
    <br>
    <a href="profile.php">Back to Profile</a>
</body>

</html>