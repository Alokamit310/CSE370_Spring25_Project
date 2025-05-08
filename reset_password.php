<?php
session_start();
include 'config.php';

if (!isset($_GET['token'])) {
    header('Location: login.php');
    exit();
}

$token = $_GET['token'];

// Check if token exists and is not expired
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error);
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Invalid or expired reset link. Please request a new password reset.";
    header('Location: forgot_password.php');
    exit();
}

$email = $result->fetch_assoc()['email'];
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Update password
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        if ($updateStmt === false) {
            die('Error preparing update statement: ' . $conn->error);
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt->bind_param("ss", $hashed_password, $email);
        
        if ($updateStmt->execute()) {
            // Delete used token
            $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $deleteStmt->bind_param("s", $token);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            $_SESSION['message'] = "Password has been reset successfully. You can now login with your new password.";
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['error'] = "Error resetting password. Please try again.";
        }
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Student Companion</title>
    <link rel="stylesheet" href="forgot_password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="forgot-password-container">
        <h2><i class="fas fa-key"></i> Reset Password</h2>
        <p class="description">Enter your new password below.</p>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="New Password" required minlength="8">
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8">
            </div>
            <button type="submit">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>