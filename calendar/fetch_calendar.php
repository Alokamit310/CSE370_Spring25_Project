<?php
session_start();
require_once '../config.php';

// Create calendar_events table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS calendar_events (
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

$conn->query($sql);

// Handle event creation/deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_event'])) {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);
        $color = $conn->real_escape_string($_POST['color']);
        $is_important = isset($_POST['is_important']) ? 1 : 0;
        $user_id = $_SESSION['user_id'];

        $sql = "INSERT INTO calendar_events (user_id, title, description, start_date, end_date, color, is_important) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssi", $user_id, $title, $description, $start_date, $end_date, $color, $is_important);
        $stmt->execute();

        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => true, 'message' => 'Event added successfully']);
            exit;
        }
    } elseif (isset($_POST['delete_event'])) {
        $event_id = (int)$_POST['event_id'];
        $user_id = $_SESSION['user_id'];

        $sql = "DELETE FROM calendar_events WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $event_id, $user_id);
        $stmt->execute();

        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

// Get upcoming events for sidebar
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM calendar_events 
        WHERE user_id = ? AND start_date >= CURRENT_DATE() 
        ORDER BY start_date ASC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_events = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Student Companion</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.js'></script>
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
            --fc-border-color: rgba(0,0,0,0.1);
            --fc-page-bg-color: #ffffff;
            --fc-neutral-bg-color: rgba(208, 208, 208, 0.3);
            --fc-event-bg-color: #4a90e2;
            --fc-event-border-color: #4a90e2;
            --fc-event-text-color: #ffffff;
            --fc-neutral-text-color: #808080;
            --fc-button-bg-color: #4a90e2;
            --fc-button-border-color: #4a90e2;
            --fc-button-hover-bg-color: #357abd;
            --fc-button-hover-border-color: #357abd;
            --fc-button-active-bg-color: #357abd;
            --fc-today-bg-color: rgba(74, 144, 226, 0.1);
        }

        [data-theme="dark"] {
            --primary-color: #60a5fa;
            --secondary-color: #3b82f6;
            --background: #1a1a1a;
            --card-background: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --border-color: rgba(255,255,255,0.05);
            --fc-border-color: rgba(255,255,255,0.1);
            --fc-page-bg-color: #2d2d2d;
            --fc-neutral-bg-color: rgba(50, 50, 50, 0.3);
            --fc-event-bg-color: #60a5fa;
            --fc-event-border-color: #60a5fa;
            --fc-event-text-color: #ffffff;
            --fc-neutral-text-color: #a0aec0;
            --fc-button-bg-color: #60a5fa;
            --fc-button-border-color: #60a5fa;
            --fc-button-hover-bg-color: #3b82f6;
            --fc-button-hover-border-color: #3b82f6;
            --fc-button-active-bg-color: #3b82f6;
            --fc-today-bg-color: rgba(96, 165, 250, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background);
            margin: 0;
            padding: 2rem;
            min-height: 100vh;
            transition: background-color 0.3s ease;
            color: var(--text-primary);
        }

        .calendar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .sidebar {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: 15px;
            height: fit-content;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-form {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--background);
            color: var(--text-primary);
            box-sizing: border-box;
        }

        .color-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .color-option {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .color-option.active {
            border-color: var(--primary-color);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .form-check .form-control {
            width: auto;
        }

        .action-btn {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .action-btn:hover {
            background: var(--secondary-color);
        }

        .upcoming-events {
            margin-top: 1.5rem;
        }

        .upcoming-events h2 {
            font-size: 1.2rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .event-item {
            padding: 1rem;
            background: var(--background);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 4px solid var(--primary-color);
        }

        .event-item.important {
            border-left-color: var(--danger-color);
        }

        .event-title {
            margin: 0;
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 500;
        }

        .event-time {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }

        .main-calendar {
            background: var(--card-background);
            padding: 2rem;
            border-radius: 15px;
        }

        /* FullCalendar Dark Mode Styles */
        .fc {
            background: var(--card-background);
            border-radius: 8px;
            overflow: hidden;
            color: var(--text-primary);
        }

        .fc .fc-view-harness {
            background-color: var(--fc-page-bg-color);
        }

        .fc .fc-toolbar-title {
            color: var(--text-primary);
        }

        .fc-theme-standard td, 
        .fc-theme-standard th {
            border-color: var(--fc-border-color);
        }

        .fc-theme-standard .fc-scrollgrid {
            border-color: var(--fc-border-color);
        }

        .fc-col-header-cell {
            background-color: var(--fc-neutral-bg-color);
        }

        .fc-col-header-cell-cushion {
            color: var(--text-primary);
        }

        .fc-daygrid-day-number {
            color: var(--text-primary);
        }

        .fc-day-today {
            background: var(--fc-today-bg-color) !important;
        }

        .fc-button-primary {
            background-color: var(--fc-button-bg-color) !important;
            border-color: var(--fc-button-border-color) !important;
            color: white !important;
        }

        .fc-button-primary:hover {
            background-color: var(--fc-button-hover-bg-color) !important;
            border-color: var(--fc-button-hover-border-color) !important;
        }

        .fc-button-primary:not(:disabled).fc-button-active,
        .fc-button-primary:not(:disabled):active {
            background-color: var(--fc-button-active-bg-color) !important;
            border-color: var(--fc-button-active-bg-color) !important;
        }

        @media (max-width: 992px) {
            .calendar-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
        }

        #eventModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--card-background);
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            color: var(--text-primary);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--text-primary);
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Important event indicator */
        .fc-event.important-event {
            border-left: 4px solid var(--danger-color) !important;
        }
        
        .fc-event .fa-exclamation-circle {
            color: var(--danger-color);
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="calendar-container">
        <div class="sidebar">
            <div class="calendar-header">
                <h1><i class="fas fa-calendar"></i> Calendar</h1>
                <button id="theme-switch" class="action-btn">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <form id="eventForm" class="event-form">
                <div class="form-group">
                    <label for="title">Event Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="start_date">Start Date & Time</label>
                    <input type="datetime-local" id="start_date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date & Time</label>
                    <input type="datetime-local" id="end_date" name="end_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Event Color</label>
                    <div class="color-group">
                        <div class="color-option active" style="background: #4a90e2;" data-color="#4a90e2"></div>
                        <div class="color-option" style="background: #2ecc71;" data-color="#2ecc71"></div>
                        <div class="color-option" style="background: #e74c3c;" data-color="#e74c3c"></div>
                        <div class="color-option" style="background: #f1c40f;" data-color="#f1c40f"></div>
                        <div class="color-option" style="background: #9b59b6;" data-color="#9b59b6"></div>
                    </div>
                    <input type="hidden" name="color" id="color" value="#4a90e2">
                </div>
                <div class="form-check">
                    <input type="checkbox" id="is_important" name="is_important">
                    <label for="is_important">Mark as Important</label>
                </div>
                <button type="submit" name="add_event" class="action-btn">
                    <i class="fas fa-plus"></i> Add Event
                </button>
            </form>

            <div class="upcoming-events">
                <h2><i class="fas fa-clock"></i> Upcoming Events</h2>
                <?php if ($upcoming_events->num_rows > 0): ?>
                    <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                        <div class="event-item <?php echo $event['is_important'] ? 'important' : ''; ?>"
                            style="border-left-color: <?php echo htmlspecialchars($event['color']); ?>">
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <div class="event-time">
                                <i class="fas fa-calendar-day"></i> 
                                <?php echo date('M d, Y H:i', strtotime($event['start_date'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No upcoming events</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-calendar">
            <div id="calendar"></div>
        </div>
    </div>

    <div id="eventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Event Details</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button class="action-btn delete-event">Delete</button>
                <button class="action-btn close-modal">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Initialize FullCalendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: 'fetch_calendar.php',
                eventDidMount: function(info) {
                    // Add important indicator
                    if (info.event.extendedProps.is_important) {
                        info.el.classList.add('important-event');
                        
                        // Add exclamation icon to important events
                        const titleEl = info.el.querySelector('.fc-event-title');
                        if (titleEl) {
                            const icon = document.createElement('i');
                            icon.className = 'fas fa-exclamation-circle';
                            icon.style.marginLeft = '5px';
                            titleEl.appendChild(icon);
                        }
                    }
                },
                eventClick: function(info) {
                    showEventModal(info.event);
                }
            });
            calendar.render();

            // Color picker functionality
            document.querySelectorAll('.color-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    document.getElementById('color').value = this.dataset.color;
                });
            });

            // Form submission
            document.getElementById('eventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('ajax', '1');

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        calendar.refetchEvents();
                        this.reset();
                        document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('active'));
                        document.querySelector('.color-option[data-color="#4a90e2"]').classList.add('active');
                        document.getElementById('color').value = '#4a90e2';
                        
                        // Reload the page to update the upcoming events sidebar
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                });
            });

            // Event modal functionality
            function showEventModal(event) {
                const modal = document.getElementById('eventModal');
                const modalBody = modal.querySelector('.modal-body');
                modalBody.innerHTML = `
                    <h3>${event.title}</h3>
                    <p>${event.extendedProps.description || 'No description'}</p>
                    <p><strong>Start:</strong> ${event.start.toLocaleString()}</p>
                    <p><strong>End:</strong> ${event.end ? event.end.toLocaleString() : 'Same day'}</p>
                    ${event.extendedProps.is_important ? '<p><strong><i class="fas fa-exclamation-circle" style="color: var(--danger-color);"></i> Marked as important</strong></p>' : ''}
                `;
                
                modal.style.display = 'block';
                
                const deleteBtn = modal.querySelector('.delete-event');
                deleteBtn.onclick = function() {
                    if (confirm('Are you sure you want to delete this event?')) {
                        const formData = new FormData();
                        formData.append('delete_event', '1');
                        formData.append('event_id', event.id);
                        formData.append('ajax', '1');

                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                calendar.refetchEvents();
                                modal.style.display = 'none';
                                
                                // Reload the page to update the upcoming events sidebar
                                setTimeout(() => {
                                    window.location.reload();
                                }, 500);
                            }
                        });
                    }
                };
            }

            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.onclick = function() {
                    document.getElementById('eventModal').style.display = 'none';
                }
            });

            window.onclick = function(event) {
                const modal = document.getElementById('eventModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        });

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