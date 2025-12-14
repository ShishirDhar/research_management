<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list.php");
    exit();
}

// Access Check for Faculty
if ($_SESSION['user_type'] == 'faculty') {
    $uid = $_SESSION['uid'];
    $check_access = "
        SELECT 1 
        FROM project_funding pf
        JOIN researcher_project rp ON pf.project_id = rp.project_id
        WHERE pf.funding_id = ? AND rp.researcher_id = ?
        LIMIT 1
    ";
    $stmt_acc = $conn->prepare($check_access);
    $stmt_acc->bind_param("ss", $id, $uid);
    $stmt_acc->execute();
    if ($stmt_acc->get_result()->num_rows === 0) {
        die("Access denied: You are not involved in any project linked to this funding.");
    }
}

$error = '';
$success = '';

if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $success = "Funding details updated successfully!";
}

// Fetch Existing Funding Data
$stmt = $conn->prepare("SELECT * FROM funding WHERE funding_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$funding = $stmt->get_result()->fetch_assoc();

if (!$funding) {
    die("Funding record not found.");
}

// Fetch Currently Linked Projects
$linked_stmt = $conn->prepare("SELECT project_id FROM project_funding WHERE funding_id = ?");
$linked_stmt->bind_param("s", $id);
$linked_stmt->execute();
$linked_result = $linked_stmt->get_result();
$linked_projects_ids = [];
while ($row = $linked_result->fetch_assoc()) {
    $linked_projects_ids[] = $row['project_id'];
}

// Fetch All Projects for Dropdown
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
            // Update Funding
            $stmt_upd = $conn->prepare("UPDATE funding SET agency_name = ?, total_grant = ? WHERE funding_id = ?");
            $stmt_upd->bind_param("sds", $agency_name, $total_grant, $id);
            
            if (!$stmt_upd->execute()) {
                throw new Exception("Error updating funding: " . $stmt_upd->error);
            }

            // Update Links: Delete all and re-insert
            $stmt_del = $conn->prepare("DELETE FROM project_funding WHERE funding_id = ?");
            $stmt_del->bind_param("s", $id);
            if (!$stmt_del->execute()) {
                throw new Exception("Error clearing project links: " . $stmt_del->error);
            }

            if (!empty($selected_projects)) {
                $stmt_link = $conn->prepare("INSERT INTO project_funding (project_id, funding_id) VALUES (?, ?)");
                foreach ($selected_projects as $p_id) {
                    $stmt_link->bind_param("ss", $p_id, $id);
                    if (!$stmt_link->execute()) {
                        throw new Exception("Error linking project: " . $stmt_link->error);
                    }
                }
            }

            $conn->commit();
            header("Location: edit.php?id=$id&msg=updated");
            exit();

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
    <title>Edit Funding - RMS</title>
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
                <h1>Edit Funding Source</h1>
                <p>Update details for <?php echo htmlspecialchars($funding['agency_name']); ?>.</p>
            </div>

            <?php if ($error) echo "<div class='error-message'>$error</div>"; ?>
            <?php if ($success) echo "<div class='success-message' style='color: green; padding: 10px; background: #ecfdf5; border-radius: 6px; margin-bottom: 20px;'>$success</div>"; ?>

            <div class="form-container">
                <form action="edit.php?id=<?php echo $id; ?>" method="POST" class="modern-form">
                    <div class="form-group">
                        <label>Agency Name</label>
                        <input type="text" name="agency_name" required value="<?php echo htmlspecialchars($funding['agency_name']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Total Grant Amount (৳)</label>
                        <input type="number" name="total_grant" step="0.01" required value="<?php echo htmlspecialchars($funding['total_grant']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Link Projects</label>
                        <div class="checkbox-group">
                            <?php foreach ($projects as $p): ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="projects[]" value="<?php echo $p['project_id']; ?>" 
                                        <?php if (in_array($p['project_id'], $linked_projects_ids)) echo 'checked'; ?>>
                                    <span><?php echo htmlspecialchars($p['project_title']); ?> <small style="color: #6b7280;">(<?php echo ucfirst($p['status']); ?>)</small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Update Funding</button>
                    <a href="list.php" style="margin-left: 15px; color: #6b7280; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>

</html>
