<?php
session_start();
require_once '../config.php';

// Create todo_list table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS todo_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    task TEXT,
    priority VARCHAR(10),
    deadline DATE,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$conn->query($sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_task'])) {
        $task = $conn->real_escape_string($_POST['task']);
        $priority = $conn->real_escape_string($_POST['priority']);
        $deadline = $conn->real_escape_string($_POST['deadline']);
        $user_id = $_SESSION['user_id'];

        $sql = "INSERT INTO todo_list (user_id, task, priority, deadline) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $user_id, $task, $priority, $deadline);
        $stmt->execute();
    } elseif (isset($_POST['toggle_task'])) {
        $task_id = (int)$_POST['task_id'];
        $sql = "UPDATE todo_list SET is_completed = NOT is_completed WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
        $stmt->execute();
    } elseif (isset($_POST['delete_task'])) {
        $task_id = (int)$_POST['task_id'];
        $sql = "DELETE FROM todo_list WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
        $stmt->execute();
    }
}

// Get tasks for the user
$user_id = $_SESSION['user_id'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'deadline';

$where_clause = "user_id = ?";
if ($filter === 'pending') {
    $where_clause .= " AND is_completed = 0";
} elseif ($filter === 'completed') {
    $where_clause .= " AND is_completed = 1";
}

$order_by = match($sort) {
    'priority' => "FIELD(priority, 'High', 'Medium', 'Low'), deadline ASC",
    'deadline' => "deadline ASC, FIELD(priority, 'High', 'Medium', 'Low')",
    default => "deadline ASC"
};

$sql = "SELECT * FROM todo_list WHERE {$where_clause} ORDER BY {$order_by}";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List - Student Companion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #357abd;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --background: #f8f9fa;
            --card-background: #ffffff;
            --text-primary: #2d3436;
            --text-secondary: #636e72;
            --border-color: rgba(0,0,0,0.05);
        }

        [data-theme="dark"] {
            --primary-color: #60a5fa;
            --secondary-color: #3b82f6;
            --background: #1a1a1a;
            --card-background: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --border-color: rgba(255,255,255,0.05);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background);
            margin: 0;
            padding: 2rem;
            min-height: 100vh;
            transition: background-color 0.3s ease;
        }

        .todo-container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--card-background);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px var(--border-color);
        }

        .todo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .todo-header h1 {
            color: var(--text-primary);
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .filter-group {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            background: var(--background);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .task-form {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--background);
            padding: 1rem;
            border-radius: 10px;
        }

        .task-input {
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-background);
            color: var(--text-primary);
        }

        .priority-select, .deadline-input {
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-background);
            color: var(--text-primary);
        }

        .add-btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background: var(--secondary-color);
        }

        .task-list {
            display: grid;
            gap: 1rem;
        }

        .task-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--background);
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .task-item:hover {
            transform: translateX(5px);
        }

        .task-item.priority-high { border-left-color: var(--danger-color); }
        .task-item.priority-medium { border-left-color: var(--warning-color); }
        .task-item.priority-low { border-left-color: var(--success-color); }

        .task-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .task-content {
            flex-grow: 1;
        }

        .task-content h3 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1rem;
            text-decoration: none;
        }

        .task-item.completed .task-content h3 {
            text-decoration: line-through;
            opacity: 0.7;
        }

        .task-meta {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }

        .task-actions {
            display: flex;
            gap: 0.5rem;
        }

        .delete-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 50%;
            background: var(--danger-color);
            color: white;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .task-item:hover .delete-btn {
            opacity: 1;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            margin-top: 2rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .task-form {
                grid-template-columns: 1fr;
            }

            .controls {
                flex-direction: column;
            }

            .filter-group {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="todo-container">
        <div class="todo-header">
            <h1><i class="fas fa-tasks"></i> To-Do List</h1>
            <button id="theme-switch" aria-label="Toggle dark mode" class="filter-btn">
                <i class="fas fa-moon"></i>
            </button>
        </div>

        <div class="controls">
            <div class="filter-group">
                <a href="?filter=all&sort=<?php echo $sort; ?>" 
                   class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All Tasks
                </a>
                <a href="?filter=pending&sort=<?php echo $sort; ?>" 
                   class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="?filter=completed&sort=<?php echo $sort; ?>" 
                   class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                    Completed
                </a>
            </div>
            <div class="filter-group">
                <a href="?filter=<?php echo $filter; ?>&sort=deadline" 
                   class="filter-btn <?php echo $sort === 'deadline' ? 'active' : ''; ?>">
                    Sort by Date
                </a>
                <a href="?filter=<?php echo $filter; ?>&sort=priority" 
                   class="filter-btn <?php echo $sort === 'priority' ? 'active' : ''; ?>">
                    Sort by Priority
                </a>
            </div>
        </div>

        <form method="POST" class="task-form">
            <input type="text" name="task" class="task-input" placeholder="Enter new task" required>
            <select name="priority" class="priority-select" required>
                <option value="Low">Low Priority</option>
                <option value="Medium">Medium Priority</option>
                <option value="High">High Priority</option>
            </select>
            <input type="date" name="deadline" class="deadline-input" required>
            <button type="submit" name="add_task" class="add-btn">
                <i class="fas fa-plus"></i> Add Task
            </button>
        </form>

        <div class="task-list">
            <?php if ($tasks->num_rows > 0): ?>
                <?php while ($task = $tasks->fetch_assoc()): ?>
                    <div class="task-item priority-<?php echo strtolower($task['priority']); ?> 
                                <?php echo $task['is_completed'] ? 'completed' : ''; ?>">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <input type="checkbox" 
                                   class="task-checkbox" 
                                   <?php echo $task['is_completed'] ? 'checked' : ''; ?>
                                   onChange="this.form.submit()" 
                                   name="toggle_task">
                        </form>
                        <div class="task-content">
                            <h3><?php echo htmlspecialchars($task['task']); ?></h3>
                            <div class="task-meta">
                                <span><i class="fas fa-calendar"></i> Due: <?php echo date('M d, Y', strtotime($task['deadline'])); ?></span>
                                <span><i class="fas fa-flag"></i> <?php echo $task['priority']; ?> Priority</span>
                            </div>
                        </div>
                        <div class="task-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" name="delete_task" class="delete-btn">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                    <i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>No tasks found. Add your first task to get started!</p>
                </div>
            <?php endif; ?>
        </div>

        <a href="../dashboard.php" class="back-btn">
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