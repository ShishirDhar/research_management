<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty Only
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list.php");
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM project WHERE project_id = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        header("Location: list.php?msg=deleted");
    } else {
        throw new Exception("Error deleting project: " . $stmt->error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>