<?php
require_once 'config/db_connect.php';

try {
    $sql = "ALTER TABLE project DROP COLUMN publication_type";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'publication_type' dropped successfully.";
    } else {
        echo "Error dropping column: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>