<?php
session_start();
require_once "db_connection.php";

// Only allow logged-in teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: LoginTeacher.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch teacher info
$teacher = $conn->query("SELECT * FROM teachers WHERE user_id = $user_id")->fetch_assoc();

// Total classes/subjects assigned
$total_classes = $conn->query("
    SELECT COUNT(*) as total 
    FROM classes 
    WHERE teacher_id = {$teacher['teacher_id']}
")->fetch_assoc()['total'];

// Total students handled
$total_students = $conn->query("
    SELECT COUNT(DISTINCT e.student_number) AS total
    FROM classes c
    JOIN enrollment_subjects es ON es.subject_id = c.subject_id
    JOIN enrollments e ON e.enrollment_id = es.enrollment_id
    WHERE c.teacher_id = {$teacher['teacher_id']}
      AND e.archived = 'no'
")->fetch_assoc()['total'];

$conn->query("UPDATE enrollments SET archived='yes' WHERE archived='no'");
// Upcoming classes (next 3)
$upcoming_classes = $conn->query("
    SELECT subject_id, days, schedule_time, room
    FROM classes
    WHERE teacher_id = {$teacher['teacher_id']}
    ORDER BY schedule_time ASC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

// Pending grades
$pending_grades = $conn->query("
    SELECT COUNT(*) as total
    FROM grades g
    JOIN classes c ON g.class_id = c.class_id
    WHERE c.teacher_id = {$teacher['teacher_id']}
      AND g.grade_value IS NULL
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/teacherDashboard.css">
    <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
    <title>Teacher Dashboard</title>
    <style>
    /* Simple card styling for dashboard blocks */
    .parent {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .parent div {
        background: #f5f5f5;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .parent h3 {
        margin-top: 0;
        font-size: 1.2rem;
    }
    .parent ul {
        padding-left: 20px;
    }
    button {
        padding: 8px 15px;
        margin: 5px 0;
        border: none;
        border-radius: 5px;
        background-color: #007bff;
        color: white;
        cursor: pointer;
    }
    button:hover {
        background-color: #0056b3;
    }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="ascot">
        <div class="logo">
            <img src="assets/ASCOT LOGO.png" class="ascot-logo">
            <span>Aurora State College Of Technology</span>
        </div>
    </div>

    <nav>
      <a href="teacherDashboard.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/home_1_1.svg" class="icon-logo"><span>Home</span></a>
      <a href="teacherSchedules.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/calendar.svg" class="icon-logo"><span>Schedule</span></a>
      <a href="gradeInput.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/paper_1_add.svg" class="icon-logo"><span>Input Grades</span></a>
      <a href="teacherMasterlist.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/data_1.svg" class="icon-logo"><span>View Masterlist</span></a>
      <a href="logout.php" class="logout"><img src="assets/logout-svgrepo-com.svg" class="icon-logo"><span>Logout</span></a>
    </nav>

    <button><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/menu_1.svg" id="toggleBtn" class="button"></button>
</aside>

<main class="content">
    <div>
        <h1>Hi, <?= htmlspecialchars($teacher['name']) ?></h1>
        <h2>Welcome back!</h2>
    </div>

    <div class="parent">
        <div class="div1">
            <h3>Total Classes</h3>
            <p><?= $total_classes ?></p>
        </div>
        <div class="div2">
            <h3>Total Students</h3>
            <p><?= $total_students ?></p>
        </div>
        <div class="div3">
            <h3>Upcoming Classes</h3>
            <ul>
                <?php foreach($upcoming_classes as $cls): ?>
                    <li><?= $cls['days'] ?> <?= $cls['schedule_time'] ?> - Room <?= $cls['room'] ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="div4">
            <h3>Pending Grades</h3>
            <p><?= $pending_grades ?></p>
        </div>
        <div class="div5">
            <h3>Quick Actions</h3>
            <a href="gradeInput.php"><button>Input Grades</button></a>
            <a href="teacherMasterlist.php"><button>View Masterlist</button></a>
            <a href="teacherSchedules.php"><button>View Schedule</button></a>
        </div>
    </div>
</main>

<script>
const sidebar = document.querySelector('.sidebar');
document.getElementById('toggleBtn').addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
});
</script>

</body>
</html>
