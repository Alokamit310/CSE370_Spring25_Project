<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $password_hint = trim($_POST['password_hint']);

        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Check for existing user
        $check = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        if ($check === false) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $check->bind_param("ss", $email, $username);
        if (!$check->execute()) {
            throw new Exception("Error checking existing user: " . $check->error);
        }
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $existing_user = $result->fetch_assoc();
            if ($existing_user['email'] === $email) {
                throw new Exception("Email already registered!");
            } else {
                throw new Exception("Username already taken!");
            }
        }

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, password_hint) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $password_hint);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating account: " . $stmt->error);
        }

        $_SESSION['success'] = "Registration successful! Please login with your credentials.";
        header('Location: login.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: register.php');
        exit();
    } finally {
        if (isset($check)) $check->close();
        if (isset($stmt)) $stmt->close();
        if (isset($conn)) $conn->close();
    }
}
?>