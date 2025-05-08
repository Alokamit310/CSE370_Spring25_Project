<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

$student_id = $_SESSION['user_id'];

// Get user data first
$stmt = $conn->prepare("SELECT username, email, institution, current_semester, academic_goal, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $institution = trim($_POST['institution']);
    $current_semester = intval($_POST['current_semester']);
    $academic_goal = trim($_POST['academic_goal']);
    
    // Handle username, email and password updates
    if (isset($_POST['new_username']) && !empty($_POST['new_username'])) {
        $new_username = trim($_POST['new_username']);
        // Check if username is already taken
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $new_username, $student_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $_SESSION['error'] = "Username already taken!";
            header("Location: profile.php");
            exit();
        }
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $new_username, $student_id);
        $stmt->execute();
        $user['username'] = $new_username;
    }

    if (isset($_POST['new_email']) && !empty($_POST['new_email'])) {
        $new_email = trim($_POST['new_email']);
        // Check if email is already registered
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $new_email, $student_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $_SESSION['error'] = "Email already registered!";
            header("Location: profile.php");
            exit();
        }
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $new_email, $student_id);
        $stmt->execute();
        $user['email'] = $new_email;
    }

    if (isset($_POST['new_password']) && !empty($_POST['new_password']) && isset($_POST['current_password']) && !empty($_POST['current_password'])) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($_POST['current_password'], $result['password'])) {
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password, $student_id);
            $stmt->execute();
            $_SESSION['message'] = "Password updated successfully!";
        } else {
            $_SESSION['error'] = "Current password is incorrect!";
            header("Location: profile.php");
            exit();
        }
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'profile_' . $student_id . '_' . time() . '.' . $filetype;
            $upload_path = $upload_dir . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if it exists
                if (!empty($user['profile_picture'])) {
                    $old_file = $upload_dir . '/' . $user['profile_picture'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $new_filename, $student_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Profile picture updated successfully!";
                    // Refresh user data to show new image immediately
                    $user['profile_picture'] = $new_filename;
                } else {
                    $_SESSION['error'] = "Error updating profile picture in database.";
                }
            } else {
                $_SESSION['error'] = "Error uploading profile picture. Please check directory permissions.";
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Please upload a JPG, JPEG, PNG, or GIF file.";
        }
    }
    
    // Update other profile information
    $stmt = $conn->prepare("UPDATE users SET institution = ?, current_semester = ?, academic_goal = ? WHERE id = ?");
    $stmt->bind_param("sisi", $institution, $current_semester, $academic_goal, $student_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating profile.";
    }
    header("Location: profile.php");
    exit();
}

// Get stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_courses, AVG(marks) as average FROM grades WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="profile-container">
        <h2><i class="fas fa-user-circle"></i> Student Profile</h2>
        
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

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-info">
                    <form method="POST" enctype="multipart/form-data" id="profile-picture-form">
                        <div class="profile-picture-container">
                            <?php if ($user['profile_picture']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                            <?php else: ?>
                                <div class="profile-avatar">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <label for="profile_picture" class="change-picture">
                                <i class="fas fa-camera"></i>
                                <span>Change Picture</span>
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display: none">
                        </div>
                    </form>
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="theme-toggle">
                    <button id="theme-switch" aria-label="Toggle dark mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="profile-form">
                <div class="profile-details">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="new_username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="New Username">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="new_email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="New Email">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" name="current_password" placeholder="Current Password">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" name="new_password" placeholder="New Password">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-university"></i> Institution</label>
                        <input type="text" name="institution" value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>" placeholder="Your Institution Name">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Current Semester</label>
                        <input type="number" name="current_semester" value="<?php echo htmlspecialchars($user['current_semester'] ?? ''); ?>" min="1" max="12" placeholder="Current Semester">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-bullseye"></i> Academic Goal</label>
                        <textarea name="academic_goal" placeholder="What do you want to achieve this semester?"><?php echo htmlspecialchars($user['academic_goal'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="save-profile">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <h4><i class="fas fa-book"></i> Total Courses</h4>
                    <div class="stat-value"><?php echo $stats['total_courses']; ?></div>
                </div>
                <div class="stat-item">
                    <h4><i class="fas fa-chart-line"></i> Average Score</h4>
                    <div class="stat-value"><?php echo number_format($stats['average'] ?? 0, 1); ?>%</div>
                </div>
            </div>
        </div>
        
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
        // Theme switching functionality
        const themeSwitch = document.getElementById('theme-switch');
        const icon = themeSwitch.querySelector('i');

        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme') || 
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        // Apply initial theme
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateIcon(savedTheme);

        // Handle theme toggle
        themeSwitch.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
        });

        function updateIcon(theme) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // Auto-submit form when profile picture is changed
        document.getElementById('profile_picture').onchange = function() {
            if (this.files && this.files[0]) {
                document.getElementById('profile-picture-form').submit();
            }
        };
    </script>
</body>
</html>