<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_id = $_POST['subject_id'];
    $target_dir = "uploads/$subject_id/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file = $_FILES['file'];
    $file_name = basename($file['name']);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate file type
    $allowed_types = array('pdf', 'docx', 'pptx', 'zip');
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid file type.'));
        exit;
    }

    // Save file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Update study_tasks table with file_path
        $stmt = $conn->prepare("UPDATE study_tasks SET file_path = ? WHERE subject_id = ?");
        $stmt->bind_param('ss', $target_file, $subject_id);
        if ($stmt->execute()) {
            echo json_encode(array('status' => 'success', 'message' => 'File uploaded successfully.'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to update database.'));
        }
        $stmt->close();
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Failed to upload file.'));
    }
}
?>