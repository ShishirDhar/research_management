<?php
require_once 'config/db_connect.php';

try {
    $sql = "ALTER TABLE project ADD COLUMN publication_type VARCHAR(50) DEFAULT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'publication_type' added successfully.";
    } else {
        // Check if error is due to column already existing
        if (strpos($conn->error, "Duplicate column name") !== false) {
            echo "Column 'publication_type' already exists.";
        } else {
            echo "Error adding column: " . $conn->error;
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>