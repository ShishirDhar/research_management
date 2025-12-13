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
    die("Invalid Request");
}

// Fetch Existing Expenditure
$stmt = $conn->prepare("SELECT * FROM expenditures WHERE expenditure_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$exp = $stmt->get_result()->fetch_assoc();

if (!$exp) {
    die("Expenditure record not found.");
}

$funding_id = $exp['funding_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $date = $_POST['expenditure_date'];
    $details = trim($_POST['details']);

    if (empty($amount) || empty($date) || empty($details)) {
        $error = "All fields are required.";
    } else {
        // Validation: Check if new amount exceeds limit + current amount (since we are replacing it)
        $stmt_bal = $conn->prepare("SELECT amount_left FROM funding_with_amount_left WHERE funding_id = ?");
        $stmt_bal->bind_param("s", $funding_id);
        $stmt_bal->execute();
        $bal = $stmt_bal->get_result()->fetch_assoc();

        // Available for this update = Remaining in pot + What this expense currently holds
        $available_limit = ($bal ? $bal['amount_left'] : 0) + $exp['amount'];

        if ($amount > $available_limit) {
            $error = "Insufficient funds. Max allocatable: ৳" . number_format($available_limit);
        } else {
            $conn->begin_transaction();
            try {
                // Update Expenditure
                $stmt_upd = $conn->prepare("UPDATE expenditures SET amount=?, expenditure_date=?, details=? WHERE expenditure_id=?");
                $stmt_upd->bind_param("dsss", $amount, $date, $details, $id);

                if (!$stmt_upd->execute()) {
                    throw new Exception("Error updating expenditure: " . $stmt_upd->error);
                }

                $conn->commit();
                header("Location: view.php?id=$funding_id&msg=exp_updated");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expenditure - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Edit Expenditure</h1>
                <p>Update expense details.</p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>

            <div class="form-container">
                <form action="expenditure_edit.php?id=<?php echo $id; ?>" method="POST" class="modern-form">
                    <div class="form-group">
                        <label>Amount (৳)</label>
                        <input type="number" name="amount" step="0.01" required
                            value="<?php echo htmlspecialchars($exp['amount']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expenditure_date" required
                            value="<?php echo htmlspecialchars($exp['expenditure_date']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Details / Purpose</label>
                        <textarea name="details" rows="3"
                            required><?php echo htmlspecialchars($exp['details']); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Update Expenditure</button>
                    <a href="view.php?id=<?php echo $funding_id; ?>"
                        style="margin-left: 15px; color: #6b7280; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>

</html>