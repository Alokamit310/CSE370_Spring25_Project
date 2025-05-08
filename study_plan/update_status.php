<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE study_tasks SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $task_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
}
?>