<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../lib/auth.php';

require_login();

$uid = $_SESSION['uid'];

// Fetch Researcher Info
$query = "SELECT r.f_name, r.l_name, r.email, r.department, rp.biography, rp.research_interests 
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
    <title>My Profile - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
            height: fit-content;
        }

        .avatar-placeholder {
            width: 120px;
            height: 120px;
            background: #e0e7ff;
            color: #4f46e5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            margin: 0 auto 20px;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 5px;
        }

        .profile-meta {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .info-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-content {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .info-content p {
            margin-bottom: 10px;
        }

        @media (max-width: 800px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Manage your personal information and research details.</p>
            </div>

            <div class="profile-container">
                <!-- Sidebar Card -->
                <div class="profile-card">
                    <div class="avatar-placeholder">
                        <?php echo strtoupper(substr($user['f_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-name">
                        <?php echo htmlspecialchars($user['f_name'] . ' ' . $user['l_name']); ?>
                    </div>
                    <div class="profile-meta">
                        <?php echo htmlspecialchars($user['department']); ?><br>
                        <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>

                    <a href="profile_edit.php" class="btn-primary"
                        style="display: block; width: 100%; text-align: center;">Edit Profile</a>
                </div>

                <!-- Main Info -->
                <div class="info-section">
                    <div class="info-label">Biography</div>
                    <div class="info-content">
                        <?php echo $user['biography'] ? nl2br(htmlspecialchars($user['biography'])) : '<em style="color:#9ca3af;">No biography added yet.</em>'; ?>
                    </div>

                    <div style="border-top: 1px solid #e5e7eb; margin: 20px 0;"></div>

                    <div class="info-label">Research Interests</div>
                    <div class="info-content">
                        <?php echo $user['research_interests'] ? nl2br(htmlspecialchars($user['research_interests'])) : '<em style="color:#9ca3af;">No research interests added yet.</em>'; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</body>

</html>