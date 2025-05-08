<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

// Initialize variables with default values
$grades = null;
$average_score = 0;
$upcoming_tasks = null;
$upcoming_events = null;
$total_courses = 0;

// Get student's grades
$student_id = $_SESSION['user_id'];

// Create grades table if it doesn't exist
$create_grades = "CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_name VARCHAR(255),
    marks INT,
    grade VARCHAR(2),
    FOREIGN KEY (student_id) REFERENCES users(id)
)";
$conn->query($create_grades);

// Get grades
$grades_query = "SELECT course_name, marks, grade FROM grades WHERE student_id = ? ORDER BY id DESC";
if ($stmt = $conn->prepare($grades_query)) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $grades = $stmt->get_result();
    $total_courses = $grades ? $grades->num_rows : 0;
    $stmt->close();
}

// Calculate average
if ($total_courses > 0) {
    $avg_query = "SELECT AVG(marks) as average FROM grades WHERE student_id = ?";
    if ($stmt = $conn->prepare($avg_query)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $avg_result = $stmt->get_result()->fetch_assoc();
        $average_score = $avg_result['average'] ? round($avg_result['average'], 1) : 0;
        $stmt->close();
    }
}

// Create todo_list table if it doesn't exist
$create_todo = "CREATE TABLE IF NOT EXISTS todo_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    task TEXT,
    priority VARCHAR(10),
    deadline DATE,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$conn->query($create_todo);

// Get upcoming tasks
$todo_query = "SELECT task, priority, deadline FROM todo_list 
               WHERE user_id = ? AND is_completed = 0 
               AND deadline >= CURDATE()
               ORDER BY deadline ASC LIMIT 5";
if ($stmt = $conn->prepare($todo_query)) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $upcoming_tasks = $stmt->get_result();
    $stmt->close();
}

// Create calendar_events table if it doesn't exist
$create_calendar = "CREATE TABLE IF NOT EXISTS calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100),
    description TEXT,
    start_date DATETIME,
    end_date DATETIME,
    color VARCHAR(20) DEFAULT '#4a90e2',
    is_important BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$conn->query($create_calendar);

// Get upcoming events
$events_query = "SELECT title, start_date, is_important FROM calendar_events 
                WHERE user_id = ? 
                AND start_date >= CURRENT_DATE()
                ORDER BY start_date ASC LIMIT 5";
if ($stmt = $conn->prepare($events_query)) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $upcoming_events = $stmt->get_result();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="welcome-section">
                <i class="fas fa-user-graduate"></i>
                <div>
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                    <span class="last-login">Dashboard Overview</span>
                </div>
            </div>
            <div class="theme-toggle">
                <button id="theme-switch" aria-label="Toggle dark mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="quick-stats">
            <?php if ($upcoming_tasks || $upcoming_events): ?>
            <div class="stat-card animate-in notifications-card" style="animation-delay: 0.2s">
                <h3><i class="fas fa-bell"></i> Upcoming</h3>
                <div class="notifications-preview">
                    <?php if ($upcoming_tasks && $upcoming_tasks->num_rows > 0): ?>
                        <?php while ($task = $upcoming_tasks->fetch_assoc()): ?>
                            <div class="notification-item priority-<?php echo strtolower($task['priority']); ?>">
                                <i class="fas fa-tasks"></i>
                                <div class="notification-content">
                                    <span class="notification-text"><?php echo htmlspecialchars($task['task']); ?></span>
                                    <span class="notification-date">Due: <?php echo date('M d', strtotime($task['deadline'])); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="grades.php" class="action-button animate-in" style="animation-delay: 0.2s">
                <i class="fas fa-plus"></i> Add Grade
            </a>
            <a href="feedback.php" class="action-button animate-in" style="animation-delay: 0.3s">
                <i class="fas fa-chart-bar"></i> Academic Insights and Suggestions
            </a>
            <a href="todo/todo.php" class="action-button animate-in" style="animation-delay: 0.4s">
                <i class="fas fa-tasks"></i> To-Do List
            </a>
            <a href="calendar/calendar.php" class="action-button animate-in" style="animation-delay: 0.5s">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a href="study_plan/study_plan.php" class="action-button animate-in" style="animation-delay: 0.6s">
                <i class="fas fa-lightbulb"></i> Study Plan
            </a>
            <a href="profile.php" class="action-button animate-in" style="animation-delay: 0.7s">
                <i class="fas fa-user"></i> My Profile
            </a>
        </div>

        <footer class="dashboard-footer">
            <a href="logout.php" class="logout-btn animate-in" style="animation-delay: 0.8s">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </footer>
    </div>

    <script>
        // Theme switching functionality
        const themeSwitch = document.getElementById('theme-switch');
        const icon = themeSwitch.querySelector('i');

        const savedTheme = localStorage.getItem('theme') || 
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateIcon(savedTheme);

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