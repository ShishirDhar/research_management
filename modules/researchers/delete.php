<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Only Admin
if ($_SESSION['user_type'] != 'admin') {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list.php");
    exit();
}

$conn->begin_transaction();

try {
    // Delete from researcher table - Relying on ON DELETE CASCADE for related tables
    $stmt = $conn->prepare("DELETE FROM researcher WHERE researcher_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();

    $conn->commit();
    header("Location: list.php?msg=deleted");
} catch (Exception $e) {
    $conn->rollback();
    echo "Error deleting record: " . $e->getMessage();
}
?>