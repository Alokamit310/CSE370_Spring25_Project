<?php
require_once 'database.php';

// Handle GET request to fetch tasks for a subject
// Inputs: subject_id
// Return tasks as JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];

    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE subject_id = ?');
    $stmt->execute([$subject_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($tasks);
}
?>