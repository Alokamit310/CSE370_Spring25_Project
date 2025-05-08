<?php
include 'config.php';

// Drop the existing table if it exists
$sql = "DROP TABLE IF EXISTS password_resets";
if ($conn->query($sql) === TRUE) {
    echo "Existing table dropped successfully<br>";
} else {
    echo "Error dropping table: " . $conn->error . "<br>";
}

// Create the new table
$sql = "CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    INDEX (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table password_resets created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>