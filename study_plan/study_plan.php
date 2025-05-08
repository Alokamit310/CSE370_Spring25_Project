<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
include '../config.php';

// Create tables if they don't exist
$create_subjects = "CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    subject_name VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$create_tasks = "CREATE TABLE IF NOT EXISTS study_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT,
    task_title VARCHAR(255),
    description TEXT,
    deadline DATE,
    status ENUM('To-Do', 'In Progress', 'Completed') DEFAULT 'To-Do',
    file_path VARCHAR(255),
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
)";

$conn->query($create_subjects);
$conn->query($create_tasks);

// Get subjects for current user
$user_id = $_SESSION['user_id'];
$subjects_query = "SELECT * FROM subjects WHERE user_id = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subjects = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Plan - Student Companion</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .study-plan-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .study-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .subject-card {
            background: var(--card-background);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px var(--border-color);
        }

        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .task-list {
            margin-top: 1rem;
        }

        .task-item {
            background: var(--background);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .add-subject-form {
            background: var(--card-background);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--background);
            color: var(--text-primary);
        }

        .action-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .action-btn:hover {
            background: var(--secondary-color);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
            text-decoration: none;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .status-todo { background: #ffecb3; color: #856404; }
        .status-progress { background: #b3e5fc; color: #01579b; }
        .status-completed { background: #c8e6c9; color: #1b5e20; }
    </style>
</head>
<body>
    <div class="study-plan-container">
        <a href="../dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="study-header">
            <h1><i class="fas fa-book"></i> Study Plan</h1>
            <button class="action-btn" onclick="toggleAddSubject()">
                <i class="fas fa-plus"></i> Add Subject
            </button>
        </div>

        <div id="addSubjectForm" class="add-subject-form" style="display: none;">
            <h3>Add New Subject</h3>
            <form action="add_subject.php" method="POST">
                <div class="form-group">
                    <label for="subject_name">Subject Name</label>
                    <input type="text" id="subject_name" name="subject_name" required>
                </div>
                <button type="submit" class="action-btn">Save Subject</button>
            </form>
        </div>

        <div class="subject-grid">
            <?php if ($subjects->num_rows > 0): ?>
                <?php while($subject = $subjects->fetch_assoc()): ?>
                    <div class="subject-card">
                        <div class="subject-header">
                            <h3><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                            <button class="action-btn" onclick="toggleAddTask(<?php echo $subject['id']; ?>)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div id="addTaskForm_<?php echo $subject['id']; ?>" style="display: none;">
                            <form action="add_task.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                <div class="form-group">
                                    <label>Task Title</label>
                                    <input type="text" name="task_title" required>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Deadline</label>
                                    <input type="date" name="deadline" required>
                                </div>
                                <div class="form-group">
                                    <label>Attachment</label>
                                    <input type="file" name="file">
                                </div>
                                <button type="submit" class="action-btn">Add Task</button>
                            </form>
                        </div>

                        <div class="task-list">
                            <?php
                            $tasks_query = "SELECT * FROM study_tasks WHERE subject_id = ? ORDER BY deadline";
                            $task_stmt = $conn->prepare($tasks_query);
                            $task_stmt->bind_param("i", $subject['id']);
                            $task_stmt->execute();
                            $tasks = $task_stmt->get_result();
                            
                            while($task = $tasks->fetch_assoc()):
                                $status_class = 'status-' . strtolower(str_replace(' ', '', $task['status']));
                            ?>
                                <div class="task-item">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <h4><?php echo htmlspecialchars($task['task_title']); ?></h4>
                                            <p><?php echo htmlspecialchars($task['description']); ?></p>
                                            <small>Due: <?php echo date('M d, Y', strtotime($task['deadline'])); ?></small>
                                        </div>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $task['status']; ?>
                                        </span>
                                    </div>
                                    <?php if($task['file_path']): ?>
                                        <a href="<?php echo $task['file_path']; ?>" target="_blank">
                                            <i class="fas fa-paperclip"></i> View Attachment
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="subject-card" style="grid-column: 1/-1; text-align: center;">
                    <i class="fas fa-books" style="font-size: 3rem; color: var(--text-secondary);"></i>
                    <h3>No Subjects Added Yet</h3>
                    <p>Click the "Add Subject" button to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleAddSubject() {
            const form = document.getElementById('addSubjectForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function toggleAddTask(subjectId) {
            const form = document.getElementById('addTaskForm_' + subjectId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Theme support
        const savedTheme = localStorage.getItem('theme') || 
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>