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
        // Insert
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
    <title>Edit Profile - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Edit Profile</h1>
                <p>Update your biography and research interests.</p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>
            <?php if ($success)
                echo "<div style='color: #065f46; background: #d1fae5; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;'>$success</div>"; ?>

            <div class="form-container">
                <form action="profile_edit.php" method="POST" class="modern-form">
                    <div class="form-group">
                        <label>Biography</label>
                        <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 8px;">Share a brief introduction
                            about your academic background and achievements.</p>
                        <textarea name="biography" rows="6"
                            style="resize: vertical;"><?php echo htmlspecialchars($profile['biography'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Research Interests</label>
                        <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 8px;">List your key research areas
                            (e.g., AI, Machine Learning, Data Science).</p>
                        <textarea name="research_interests" rows="6"
                            style="resize: vertical;"><?php echo htmlspecialchars($profile['research_interests'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-submit">Save Changes</button>
                        <a href="profile.php"
                            style="margin-left: 20px; color: #6b7280; text-decoration: none; font-weight: 500;">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>