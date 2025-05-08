<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Create connection without database
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS student_portal";
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }

    // Select the database
    $conn->select_db("student_portal");

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        password_hint VARCHAR(255),
        profile_picture VARCHAR(255)
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Users table created successfully<br>";
    } else {
        echo "Error creating users table: " . $conn->error . "<br>";
    }

    // Create grades table
    $sql = "CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        course_name VARCHAR(255),
        marks INT,
        grade VARCHAR(2),
        FOREIGN KEY (student_id) REFERENCES users(id)
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Grades table created successfully<br>";
    } else {
        echo "Error creating grades table: " . $conn->error . "<br>";
    }

    // Create feedback table
    $sql = "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        feedback_text TEXT,
        feedback_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id)
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Feedback table created successfully<br>";
    } else {
        echo "Error creating feedback table: " . $conn->error . "<br>";
    }

    // Create password_resets table
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP,
        INDEX (email)
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Password resets table created successfully<br>";
    } else {
        echo "Error creating password resets table: " . $conn->error . "<br>";
    }

    $conn->close();
    echo "Database setup completed successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>