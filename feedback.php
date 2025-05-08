<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function calculateProgress($grades) {
    $stats = [
        'total_courses' => count($grades),
        'average' => 0,
        'highest_grade' => 0,
        'improvement_rate' => 0,
        'subjects_at_risk' => [],
        'top_subjects' => []
    ];
    
    if ($stats['total_courses'] > 0) {
        $total_marks = 0;
        foreach ($grades as $grade) {
            $total_marks += $grade['marks'];
            if ($grade['marks'] >= 80) {
                $stats['top_subjects'][] = $grade['course_name'];
            }
            if ($grade['marks'] < 60) {
                $stats['subjects_at_risk'][] = $grade['course_name'];
            }
            $stats['highest_grade'] = max($stats['highest_grade'], $grade['marks']);
        }
        $stats['average'] = $total_marks / $stats['total_courses'];
    }
    
    return $stats;
}

function generateRecommendations($stats) {
    $recommendations = [];
    
    if (empty($stats['subjects_at_risk'])) {
        $recommendations[] = "🌟 Great job maintaining good grades across all subjects!";
    } else {
        $recommendations[] = "📚 Consider seeking additional help for: " . implode(", ", $stats['subjects_at_risk']);
    }
    
    if ($stats['average'] >= 80) {
        $recommendations[] = "🎯 You're performing excellently! Consider taking advanced courses or helping peers.";
    } elseif ($stats['average'] >= 60) {
        $recommendations[] = "📈 You're doing well! Focus on turning your understanding into higher grades.";
    } else {
        $recommendations[] = "💪 Develop a structured study plan and consider joining study groups.";
    }
    
    if (!empty($stats['top_subjects'])) {
        $recommendations[] = "🏆 Your strongest subjects are: " . implode(", ", $stats['top_subjects']);
    }
    
    return $recommendations;
}

// Get grades from database
include 'config.php';
$student_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, course_name, marks, grade FROM grades WHERE student_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
}

$stats = calculateProgress($grades);
$recommendations = generateRecommendations($stats);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Insights and Suggestions</title>
    <link rel="stylesheet" href="feedback.css">
    <link rel="stylesheet" href="grades.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="feedback-container">
        <div class="feedback-header">
            <div>
                <h2><i class="fas fa-chart-line"></i> Academic Insights and Suggestions</h2>
                <p class="subtitle">Comprehensive Analysis and Performance Tracking</p>
            </div>
            <div class="theme-toggle">
                <button id="theme-switch" aria-label="Toggle dark mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>

        <?php if ($stats['total_courses'] > 0): ?>
            <!-- Performance Overview Cards -->
            <div class="progress-cards">
                <div class="progress-card">
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Average Score</h4>
                    <div class="value"><?php echo number_format($stats['average'], 1); ?>%</div>
                </div>
                <div class="progress-card">
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h4>Highest Grade</h4>
                    <div class="value"><?php echo $stats['highest_grade']; ?>%</div>
                </div>
                <div class="progress-card">
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h4>Total Courses</h4>
                    <div class="value"><?php echo $stats['total_courses']; ?></div>
                </div>
            </div>

            <!-- Grade Distribution Chart -->
            <div class="performance-section">
                <h3><i class="fas fa-chart-bar"></i> Grade Distribution</h3>
                <div class="chart-container">
                    <?php
                    $gradeCount = array_count_values(array_column($grades, 'grade'));
                    foreach ($gradeCount as $grade => $count) {
                        $percentage = ($count / count($grades)) * 100;
                        $colorClass = strtolower($grade[0]);
                        echo "<div class='chart-column'>";
                        echo "<div class='bar-container'>";
                        echo "<div class='bar grade-{$colorClass}' style='height: {$percentage}%'></div>";
                        echo "</div>";
                        echo "<div class='grade-label'>{$grade}</div>";
                        echo "<div class='count'>({$count})</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Detailed Grades Table -->
            <div class="grades-section">
                <h3><i class="fas fa-list"></i> Course Performance Details</h3>
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
                            <?php foreach ($grades as $grade): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                    <td><span class="marks"><?php echo $grade['marks']; ?>%</span></td>
                                    <td>
                                        <span class="grade grade-<?php echo strtolower($grade['grade'][0]); ?>">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="grades.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this grade?');">
                                            <input type="hidden" name="grade_id" value="<?php echo $grade['id']; ?>">
                                            <button type="submit" name="delete_grade" class="delete-btn">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- AI Recommendations -->
            <div class="recommendations">
                <h3><i class="fas fa-lightbulb"></i> Smart Recommendations</h3>
                <div class="recommendations-grid">
                    <?php foreach ($recommendations as $recommendation): ?>
                        <div class="recommendation-card">
                            <?php
                            $icon = 'fas fa-star';
                            if (strpos($recommendation, 'seeking additional help') !== false) {
                                $icon = 'fas fa-hand-holding-medical';
                            } elseif (strpos($recommendation, 'performing excellently') !== false) {
                                $icon = 'fas fa-award';
                            } elseif (strpos($recommendation, 'strongest subjects') !== false) {
                                $icon = 'fas fa-trophy';
                            }
                            ?>
                            <div class="recommendation-icon">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <p><?php echo htmlspecialchars($recommendation); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="grades.php" class="action-button">
                    <i class="fas fa-plus"></i> Add New Grade
                </a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>Ready to track your academic progress?</p>
                <p class="subtitle">Add your grades to receive personalized insights and recommendations!</p>
                <a href="grades.php" class="action-button">Add Your First Grade</a>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
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