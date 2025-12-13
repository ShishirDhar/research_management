<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

$error = '';
$success = '';

// Fetch Projects for Dropdown (Ongoing and Completed)
$projects_query = "SELECT project_id, project_title, status FROM project WHERE status IN ('ongoing', 'completed') ORDER BY project_title ASC";
$projects_result = $conn->query($projects_query);
$projects = [];
while ($row = $projects_result->fetch_assoc()) {
    $projects[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agency_name = trim($_POST['agency_name']);
    $total_grant = $_POST['total_grant'];
    $selected_projects = $_POST['projects'] ?? [];

    if (empty($agency_name) || empty($total_grant)) {
        $error = "Agency Name and Total Grant are required.";
    } else {
        $conn->begin_transaction();
        try {
            // Generate Funding ID
            $id_query = "SELECT funding_id FROM funding ORDER BY CAST(SUBSTRING(funding_id, 2) AS UNSIGNED) DESC LIMIT 1";
            $id_result = $conn->query($id_query);

            if ($id_result->num_rows > 0) {
                $row = $id_result->fetch_assoc();
                $num = intval(substr($row['funding_id'], 1)) + 1;
                $funding_id = "f" . $num;
            } else {
                $funding_id = "f1";
            }

            // Insert Funding
            $stmt = $conn->prepare("INSERT INTO funding (funding_id, agency_name, total_grant) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $funding_id, $agency_name, $total_grant);

            if (!$stmt->execute()) {
                throw new Exception("Error adding funding: " . $stmt->error);
            }

            // Link Projects
            if (!empty($selected_projects)) {
                $stmt_link = $conn->prepare("INSERT INTO project_funding (project_id, funding_id) VALUES (?, ?)");
                foreach ($selected_projects as $p_id) {
                    $stmt_link->bind_param("ss", $p_id, $funding_id);
                    if (!$stmt_link->execute()) {
                        throw new Exception("Error linking project: " . $stmt_link->error);
                    }
                }
            }

            $conn->commit();
            $success = "Funding added successfully!";

            // Clear form
            $agency_name = '';
            $total_grant = '';

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Funding - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
    <style>
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #d1d5db;
            padding: 10px;
            border-radius: 6px;
            background: #f9fafb;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-item input {
            width: auto;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Add Funding Source</h1>
                <p>Register a new funding agency and link projects.</p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>
            <?php if ($success)
                echo "<div class='success-message' style='color: green; padding: 10px; background: #ecfdf5; border-radius: 6px; margin-bottom: 20px;'>$success</div>"; ?>

            <div class="form-container">
                <form action="add.php" method="POST" class="modern-form">
                    <div class="form-group">
                        <label>Agency Name</label>
                        <input type="text" name="agency_name" required
                            value="<?php echo htmlspecialchars($agency_name ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Total Grant Amount (৳)</label>
                        <input type="number" name="total_grant" step="0.01" required
                            value="<?php echo htmlspecialchars($total_grant ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Link Projects (Optional)</label>
                        <div class="checkbox-group">
                            <?php foreach ($projects as $p): ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="projects[]" value="<?php echo $p['project_id']; ?>">
                                    <span><?php echo htmlspecialchars($p['project_title']); ?> <small
                                            style="color: #6b7280;">(<?php echo ucfirst($p['status']); ?>)</small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Save Funding</button>
                    <a href="list.php" style="margin-left: 15px; color: #6b7280; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>

</html>