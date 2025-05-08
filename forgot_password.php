<?php
session_start();
include 'config.php';

// Create password_resets table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    INDEX (email)
)";
$conn->query($createTable);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (isset($_POST['show_hint'])) {
        // Handle password hint request
        $stmt = $conn->prepare("SELECT password_hint FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['password_hint']) {
                $_SESSION['hint'] = "Password Hint: " . $user['password_hint'];
            } else {
                $_SESSION['error'] = "No password hint set for this account.";
            }
        } else {
            $_SESSION['error'] = "Email not found.";
        }
        header('Location: forgot_password.php');
        exit();
    } else {
        // Check if email exists in users table first
        $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
        if ($checkEmail === false) {
            die('Error preparing statement: ' . $conn->error);
        }
        
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        
        if ($result->num_rows > 0) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete any existing tokens for this email
            $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            if ($deleteStmt === false) {
                die('Error preparing delete statement: ' . $conn->error);
            }
            $deleteStmt->bind_param("s", $email);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            // Insert new token
            $insertStmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            if ($insertStmt === false) {
                die('Error preparing insert statement: ' . $conn->error);
            }
            $insertStmt->bind_param("sss", $email, $token, $expires);
            
            if ($insertStmt->execute()) {
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                
                // Email headers
                $to = $email;
                $subject = "Password Reset Request";
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: Student Companion <noreply@studentcompanion.com>' . "\r\n";
                
                // Email body
                $message = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color: #4a90e2;'>Password Reset Request</h2>
                        <p>Hello,</p>
                        <p>We received a request to reset your password. Click the button below to create a new password:</p>
                        <p style='text-align: center;'>
                            <a href='{$reset_link}' 
                               style='background: #4a90e2; 
                                      color: white; 
                                      padding: 12px 30px; 
                                      text-decoration: none; 
                                      border-radius: 5px; 
                                      display: inline-block;'>
                                Reset Password
                            </a>
                        </p>
                        <p>This link will expire in 1 hour for security reasons.</p>
                        <p>If you didn't request this reset, you can safely ignore this email.</p>
                    </div>
                </body>
                </html>";
                
                if(mail($to, $subject, $message, $headers)) {
                    $_SESSION['message'] = "Password reset instructions have been sent to your email.";
                } else {
                    $_SESSION['error'] = "Error sending email. Please try again later.";
                }
            } else {
                $_SESSION['error'] = "Error processing request. Please try again.";
            }
            $insertStmt->close();
        } else {
            // Don't reveal if email exists or not for security
            $_SESSION['message'] = "If your email exists in our system, you will receive password reset instructions.";
        }
        $checkEmail->close();
        
        header("Location: forgot_password.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Student Companion</title>
    <link rel="stylesheet" href="forgot_password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="forgot-password-container">
        <h2><i class="fas fa-lock"></i> Forgot Password</h2>
        <p class="description">Enter your email address and we'll send you instructions to reset your password.</p>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['hint'])): ?>
            <div class="alert info">
                <i class="fas fa-info-circle"></i>
                <?php echo htmlspecialchars($_SESSION['hint']); unset($_SESSION['hint']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="button-group">
                <button type="submit" name="show_hint">
                    <i class="fas fa-lightbulb"></i> Show Password Hint
                </button>
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </div>
        </form>
        
        <div class="back-to-login">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>