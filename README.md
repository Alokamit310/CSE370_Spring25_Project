# Student_companion: Your Complete Academic Partner 🎓✏️

A dynamic, full-stack web application designed to enhance student productivity, streamline task management, track academic performance, and facilitate efficient event scheduling. Built with **PHP** and **MySQL**, this project serves as a centralized personal productivity suite tailored for university students.

---

## 🚀 Key Features

### 1. User Account & Profile Management 🔒
*   **Secure Authentication**: Robust registration, secure login, and session-destroying logout system.
*   **Password Recovery**: Fully functional password recovery using secure reset tokens and optional security hints.
*   **Personalized Profiles**: View, update, and manage personal data including profile picture uploads.

### 2. Centralized Dashboard 📊
*   Acts as the central operational hub upon logging in.
*   Aggregates and displays real-time summaries such as total pending tasks and upcoming study deadlines using optimized SQL queries.

### 3. Task Management (To-Do List) 📋
*   Create, track, and highlight daily responsibilities with custom color-coded categorization.
*   Sort and filter tasks efficiently by deadline date or priority levels (High/Medium/Low).

### 4. Interactive Study Planner 📚
*   Organize schedules by individual academic subjects.
*   Add descriptions, list sub-topics, set precise deadlines, and attach essential reference files (PDF/JPG).

### 5. Grade Tracking & Performance Insights 📈
*   Input and store marks for individual courses and assessments.
*   Automated calculation of letter grades (e.g., A+, A, B, etc.) based on standard academic grading systems.
*   Algorithmic backend processing that generates tailored academic suggestions and highlights subjects at risk.

### 6. Dynamic Calendar System 📅
*   Structured monthly grid format rendering dynamic, time-based event schedules.
*   Dynamic loading and AJAX-based fetching to add, edit, or delete personal event intervals securely.

---

## 🛠️ Tech Stack

*   **Frontend**: HTML5, CSS3 (including responsive layouts and embedded PHP UI styling), CSS Animations.
*   **Backend**: PHP (Server-side routing, CRUD operations, session handling, and validation algorithms).
*   **Database**: MySQL (Relational management, optimized indexing, parameterized queries).

---

## 🗄️ Database Architecture & Schema

The underlying database maps relationships across the student ecosystem. The core entities include:

*   `User`: Keeps credential paths, email validations, and configuration states.
*   `Grade`: Stores marks and mapping data correlated to courses.
*   `Subject` & `Study_task`: Handles individual course modules and sub-topic deadlines.
*   `Calendere_event`: Tracks structural timeline logs for events and reminders.
*   `To_do_list`: Manages quick task priorities and fulfillment flags.
*   `Password_resets`: Secure storage for temporary lifecycle tokens.

---

## 📂 Repository Structure

```placeholders
├── config.php                 # Core Database connection settings
├── setup_database.php         # Initial automated database creation script
├── recreate_table.php         # Script to flush/rebuild data tables
├── index.php                  # Application landing page/routing base
├── dashboard.php              # Multi-module dashboard interface
├── login.php / logout.php     # Session authentication views & controls
├── register_process.php       # Server-side validation logic for sign-ups
├── forgot_password.php        # Password token assignment handler
├── profile.php                # User overview UI & update operations
├── todo.php                   # Task management tracking operations
├── study_plan/                # Directory managing course study planner logic
├── grades.php                 # Core grade processing module
├── calendar.php               # Grid calendar generation module
└── fetch_calendar.php         # Dynamic asynchronous loader for scheduled events
```

---

## 👥 Team Contributions

This platform was developed as part of **CSE370 (Database Systems)**, Lab Section **04**, Group **14**:

*   **Alok Sarker Amit** (ID: 23101187) — *Lead Developer*
    *   **Frontend & UI Design**: Built the core system interfaces, responsive dashboard alignments (`dashboard.css`), unified theme parameters, and interface animations.
    *   **Task Management Module**: Developed the complete frontend and backend CRUD operations for the interactive To-Do list, enabling secure session-based tracking, priority sorting, and status toggles.
    *   **Study Planner & Integration**: Programmed the modular study schedule layout (`study_plan.php`), database interactions for file attachments (PDF/JPG), and designed structural data aggregation to minimize dashboard query execution times.
    *   **Core Systems**: Co-developed the main framework foundation, authentication views, and routing mechanisms.

*   **Sumaiya Akhter Moon** (ID: 23101036) — *Core Developer*
    *   **Grade Management & Logic**: Developed the interface and the server-side grade compilation algorithms (`grades.php`) to dynamically transform numerical assessment metrics into functional letter grades.
    *   **Asynchronous Calendar Engine**: Programmed the grid generation interface (`calendar.php`) alongside dynamic asynchronous event fetch handlers (`fetch_calendar.php`) for seamless database timeline syncs.
    *   **Academic Recommendation Pipeline**: Written algorithms to read performance indices and output tailored actionable warnings for courses flagged at operational risk.
    *   **Feedback System**: Managed the application suggestion structures and data parsing metrics.

---

## 🛠️ Installation & Setup

1.  **Clone the Repository**:
    ```bash
    git clone https://github.com
    cd CSE370_Spring25_Project
    ```
2.  **Environment Setup**:
    *   Move the cloned directory to your local server path (e.g., `htdocs` in XAMPP or `www` in WampServer).
    *   Ensure **Apache** and **MySQL** are running inside your local service control panel.
3.  **Database Configuration**:
    *   Open `config.php` and configure your local MySQL credentials (`localhost`, `username`, `password`).
    *   Run `setup_database.php` via your web browser or command line to initialize schemas and generate all relational tables automatically.
4.  **Launch**:
    *   Navigate to `http://localhost/CSE370_Spring25_Project/index.php` in your web browser.
