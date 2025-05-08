<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $task_title = $_POST['task_title'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $file_path = null;

    // Handle file upload if present
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/study_tasks/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . $_FILES['file']['name'];
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            $file_path = 'uploads/study_tasks/' . $file_name;
        }
    }

    // Insert task into database
    $stmt = $conn->prepare("INSERT INTO study_tasks (subject_id, task_title, description, deadline, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $subject_id, $task_title, $description, $deadline, $file_path);
    
    if ($stmt->execute()) {
        header('Location: study_plan.php');
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>