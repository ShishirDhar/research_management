<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: /research_management/public/dashboard_faculty.php");
    exit();
}

$uid = $_SESSION['uid'];

// Verify Access before deleting
if ($_SESSION['user_type'] == 'admin') {
    $query = "SELECT task_id FROM project_task WHERE task_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $id);
} else {
    $query = "SELECT pt.task_id FROM project_task pt 
              JOIN researcher_project rp ON pt.project_id = rp.project_id 
              WHERE pt.task_id = ? AND rp.researcher_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $id, $uid);
}

$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    die("Task not found or access denied.");
}

// Delete
$del_stmt = $conn->prepare("DELETE FROM project_task WHERE task_id = ?");
$del_stmt->bind_param("s", $id);

if ($del_stmt->execute()) {
    $dashboard_url = ($_SESSION['user_type'] == 'faculty') ? '/research_management/public/dashboard_faculty.php' : '/research_management/public/dashboard.php';
    header("Location: $dashboard_url?msg=task_deleted");
} else {
    echo "Error deleting task: " . $conn->error;
}
?>