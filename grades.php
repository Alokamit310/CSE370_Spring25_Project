<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['delete_grade'])) {
    $grade_id = intval($_POST['grade_id']);
    $student_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("DELETE FROM grades WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $grade_id, $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Grade deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting grade.";
    }
    header("Location: grades.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_grade'])) {
    $student_id = $_SESSION['user_id'];
    $course_name = trim($_POST['course_name']);
    $marks = intval($_POST['marks']);
    
    if ($marks >= 0 && $marks <= 100 && !empty($course_name)) {
        // Calculate grade
        $grade = '';
        if ($marks >= 90) $grade = 'A+';
        else if ($marks >= 80) $grade = 'A';
        else if ($marks >= 70) $grade = 'B';
        else if ($marks >= 60) $grade = 'C';
        else if ($marks >= 50) $grade = 'D';
        else $grade = 'F';
        
        $stmt = $conn->prepare("INSERT INTO grades (student_id, course_name, marks, grade) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $student_id, $course_name, $marks, $grade);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Grade added successfully!";
        } else {
            $_SESSION['error'] = "Error adding grade.";
        }
    } else {
        $_SESSION['error'] = "Please enter valid course name and marks (0-100).";
    }
    header("Location: grades.php");
    exit();
}

// Get grades
$stmt = $conn->prepare("SELECT id, course_name, marks, grade FROM grades WHERE student_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$grades = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades</title>
    <link rel="stylesheet" href="grades.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="grades-container">
        <div class="page-header">
            <div class="header-content">
                <h2><i class="fas fa-book-open"></i> Manage Your Grades</h2>
                <div class="theme-toggle">
                    <button id="theme-switch" aria-label="Toggle dark mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>
        
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

        <div class="add-grade-section">
            <h3><i class="fas fa-plus"></i> Add New Grade</h3>
            <form method="POST" class="grade-form">
                <div class="form-group">
                    <i class="fas fa-book"></i>
                    <input type="text" name="course_name" placeholder="Course Name" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-percent"></i>
                    <input type="number" name="marks" placeholder="Marks (0-100)" min="0" max="100" required>
                </div>
                <button type="submit">
                    <i class="fas fa-save"></i> Save Grade
                </button>
            </form>
        </div>

        <div class="grades-list">
            <div class="section-header">
                <h3><i class="fas fa-list"></i> Recent Grades</h3>
            </div>
            <?php if($grades->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Marks</th>
                                <th>Grade</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($grade = $grades->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                    <td><span class="marks"><?php echo $grade['marks']; ?>%</span></td>
                                    <td>
                                        <span class="grade grade-<?php echo strtolower($grade['grade'][0]); ?>">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this grade?');">
                                            <input type="hidden" name="grade_id" value="<?php echo $grade['id']; ?>">
                                            <button type="submit" name="delete_grade" class="delete-btn">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <p>No grades added yet. Use the form above to add your first grade.</p>
                </div>
            <?php endif; ?>
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
    </script>
</body>
</html>
<?php $conn->close(); ?>