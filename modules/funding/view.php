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

// 1. Fetch Funding Details & Amount Left from View
// View Schema: funding_id, agency_name, total_grant, amount_left
$stmt = $conn->prepare("SELECT * FROM funding_with_amount_left WHERE funding_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$funding = $stmt->get_result()->fetch_assoc();

if (!$funding) {
    // Fallback if view doesn't have it (e.g. no expenditures? View might use INNER JOIN? hopefully LEFT JOIN)
    // Or maybe the ID is just wrong.
    // Let's try fetching from main table if view fails, just in case view is strict.
    $stmt2 = $conn->prepare("SELECT * FROM funding WHERE funding_id = ?");
    $stmt2->bind_param("s", $id);
    $stmt2->execute();
    $basic_funding = $stmt2->get_result()->fetch_assoc();

    if (!$basic_funding) {
        die("Funding record not found.");
    }

    // If found in basic but not view, it likely means NULL amount left logic in view isn't handling empty expenditures
    // We'll simulate it.
    $funding = $basic_funding;
    $funding['amount_left'] = $basic_funding['total_grant']; // Default if no expenditures
}

// 2. Fetch Expenditures
// Schema per request: funding_id, expenditure_id, amount, expenditure_date, details
// We select * to be safe if columns vary slightly, but we target these in HTML.
$stmt_exp = $conn->prepare("SELECT * FROM expenditures WHERE funding_id = ? ORDER BY expenditure_date DESC");
$stmt_exp->bind_param("s", $id);
$stmt_exp->execute();
$result_exp = $stmt_exp->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funding Details - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <div>
                    <a href="list.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Back to
                        Funding</a>
                    <h1 style="margin-top: 10px;"><?php echo htmlspecialchars($funding['agency_name']); ?></h1>
                    <p>Funding Breakdown and Expenditures</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid"
                style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
                <div class="stat-card blue">
                    <h3>Total Grant</h3>
                    <div class="value">৳<?php echo number_format($funding['total_grant']); ?></div>
                </div>
                <div class="stat-card green">
                    <h3>Amount Left</h3>
                    <div class="value">৳<?php echo number_format($funding['amount_left']); ?></div>
                </div>
                <div class="stat-card purple">
                    <h3>Expenditure Count</h3>
                    <div class="value"><?php echo $result_exp->num_rows; ?></div>
                </div>
            </div>

            <!-- Expenditures Table -->
            <div class="section-title"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">Expenditure History</h2>
                <a href="expenditure_add.php?funding_id=<?php echo $id; ?>" class="btn-primary"
                    style="background: #4f46e5; color: white; padding: 6px 15px; border-radius: 6px; text-decoration: none; font-size: 0.9rem;">+
                    Add Expenditure</a>
            </div>

            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Details</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_exp->num_rows > 0): ?>
                            <?php while ($row = $result_exp->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php
                                        // Handle potential column variations gracefully-ish
                                        $date = $row['expenditure_date'] ?? $row['date'] ?? 'N/A';
                                        echo htmlspecialchars($date);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $details = $row['details'] ?? $row['expenditure_detail'] ?? 'N/A';
                                        echo htmlspecialchars($details);
                                        ?>
                                    </td>
                                    <td>
                                        <span style="color: #dc2626; font-weight: 500;">
                                            -৳<?php echo number_format($row['amount']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="expenditure_edit.php?id=<?php echo $row['expenditure_id']; ?>"
                                            style="color: #4f46e5; text-decoration: none; margin-right: 10px;">Edit</a>
                                        <a href="expenditure_delete.php?id=<?php echo $row['expenditure_id']; ?>"
                                            style="color: #ef4444; text-decoration: none;"
                                            onclick="return confirm('Delete this expenditure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: #6b7280;">
                                    No expenditures recorded for this funding source.
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