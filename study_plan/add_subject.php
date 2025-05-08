<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = $_POST['subject_name'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO subjects (user_id, subject_name) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $subject_name);
    
    if ($stmt->execute()) {
        header('Location: study_plan.php');
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>