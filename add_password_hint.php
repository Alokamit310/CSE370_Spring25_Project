<?php
require_once 'config.php';

try {
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS password_hint VARCHAR(255) AFTER password";
    if ($conn->query($sql) === TRUE) {
        echo "Password hint column added successfully";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?>