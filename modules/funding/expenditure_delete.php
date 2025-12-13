<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin Only (or Faculty? Assuming Admin/Faculty can managing funding usually means they can delete expenses)
// Implementing stricter control: Admin only for deletion, similar to funding deletion? 
// Or consistent with Funding module: Admin & Faculty. Let's keep it consistent.
if ($_SESSION['user_type'] == 'student') {
    die("Unauthorized access.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid Request");
}

// Get Funding ID for redirect
$stmt = $conn->prepare("SELECT funding_id FROM expenditures WHERE expenditure_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$exp = $stmt->get_result()->fetch_assoc();

if (!$exp) {
    die("Expenditure record not found.");
}
$funding_id = $exp['funding_id'];

// Delete
$stmt_del = $conn->prepare("DELETE FROM expenditures WHERE expenditure_id = ?");
$stmt_del->bind_param("s", $id);

if ($stmt_del->execute()) {
    header("Location: view.php?id=$funding_id&msg=exp_deleted");
} else {
    echo "Error deleting record: " . $conn->error;
}
?>