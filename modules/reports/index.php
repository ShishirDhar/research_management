<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty Only
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

$user_id = $_SESSION['uid'];
$user_type = $_SESSION['user_type'];

$projects = [];

if ($user_type == 'admin') {
    // Admin sees all projects
    $query = "SELECT project_id, project_title, status FROM project ORDER BY project_title ASC";
    $stmt = $conn->prepare($query);
} else {
    // Faculty sees projects they are involved in (Lead or Member)
    // DISTINCT because a user might be lead AND in researcher_project (though unlikely if normalized properly, but safer)
    $query = "
        SELECT DISTINCT p.project_id, p.project_title, p.status 
        FROM project p
        LEFT JOIN researcher_project rp ON p.project_id = rp.project_id
        WHERE p.project_lead = ? OR rp.researcher_id = ?
        ORDER BY p.project_title ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $user_id, $user_id);
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Reports - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Project Reports</h1>
                <p>Generate detailed PDF reports for your projects.</p>
            </div>

            <div class="form-container" style="max-width: 600px;">
                <form action="view.php" method="GET" target="_blank" class="modern-form">
                    <div class="form-group">
                        <label>Select Project</label>
                        <select name="project_id" required>
                            <option value="">-- Choose a Project --</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['project_id']); ?>">
                                    <?php echo htmlspecialchars($p['project_title']); ?>
                                    (<?php echo ucfirst($p['status']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit"
                        style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Generate Report
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>