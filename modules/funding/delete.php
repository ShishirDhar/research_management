<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin Only for Deletion
if ($_SESSION['user_type'] != 'admin') {
    die("Unauthorized access.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list.php");
    exit();
}

$conn->begin_transaction();

try {
    // Delete links first
    $stmt_link = $conn->prepare("DELETE FROM project_funding WHERE funding_id = ?");
    $stmt_link->bind_param("s", $id);
    $stmt_link->execute();

    // Delete funding record
    $stmt = $conn->prepare("DELETE FROM funding WHERE funding_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();

    $conn->commit();
    header("Location: list.php?msg=deleted");
} catch (Exception $e) {
    $conn->rollback();
    echo "Error deleting record: " . $e->getMessage();
}
?>