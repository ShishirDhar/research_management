<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

$funding_id = $_GET['funding_id'] ?? null;
if (!$funding_id) {
    header("Location: list.php");
    exit();
}

// Check if funding exists
$stmt = $conn->prepare("SELECT agency_name FROM funding WHERE funding_id = ?");
$stmt->bind_param("s", $funding_id);
$stmt->execute();
$funding = $stmt->get_result()->fetch_assoc();

if (!$funding) {
    die("Funding record not found.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $date = $_POST['expenditure_date'];
    $details = trim($_POST['details']);

    if (empty($amount) || empty($date) || empty($details)) {
        $error = "All fields are required.";
    } else {
        // Validation: Check if amount exceeds limit
        $stmt_bal = $conn->prepare("SELECT amount_left FROM funding_with_amount_left WHERE funding_id = ?");
        $stmt_bal->bind_param("s", $funding_id);
        $stmt_bal->execute();
        $bal = $stmt_bal->get_result()->fetch_assoc();
        $limit = $bal ? $bal['amount_left'] : 0;

        // If view returns null for amount_left (rare, but if handled poorly), fallback to total_grant logic or just skip? 
        // Assuming view works.

        if ($amount > $limit) {
            $error = "Insufficient funds. Available: ৳" . number_format($limit);
        } else {
            $conn->begin_transaction();
            try {
                // Generate Expenditure ID (Integer)
            $id_query = "SELECT MAX(expenditure_id) as max_id FROM expenditures";
            $id_result = $conn->query($id_query);
            $row = $id_result->fetch_assoc();
            $exp_id = ($row['max_id'] ?? 0) + 1;

            // Insert Expenditure
            // Schema: funding_id, expenditure_id, amount, expenditure_date, details
            $stmt_ins = $conn->prepare("INSERT INTO expenditures (funding_id, expenditure_id, amount, expenditure_date, details) VALUES (?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("sidss", $funding_id, $exp_id, $amount, $date, $details);

                if (!$stmt_ins->execute()) {
                    throw new Exception("Error adding expenditure: " . $stmt_ins->error);
                }

                $conn->commit();
                $success = "Expenditure recorded successfully!";

                // Redirect back to view after short delay or link
                header("Location: view.php?id=$funding_id&msg=exp_added");
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
    <title>Add Expenditure - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Add Expenditure</h1>
                <p>Record a new expense for <strong><?php echo htmlspecialchars($funding['agency_name']); ?></strong>.
                </p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>

            <div class="form-container">
                <form action="expenditure_add.php?funding_id=<?php echo $funding_id; ?>" method="POST"
                    class="modern-form">
                    <div class="form-group">
                        <label>Amount (৳)</label>
                        <input type="number" name="amount" step="0.01" required placeholder="e.g. 5000">
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expenditure_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Details / Purpose</label>
                        <textarea name="details" rows="3" required placeholder="e.g. Equipment purchase"></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Save Expenditure</button>
                    <a href="view.php?id=<?php echo $funding_id; ?>"
                        style="margin-left: 15px; color: #6b7280; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>

</html>