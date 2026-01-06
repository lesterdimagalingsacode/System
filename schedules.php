<?php
session_start();
require_once "db_connection.php";

// Student must login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Get student_number using user_id
$user_id = $_SESSION['user_id'];
$student = $conn->query("SELECT * FROM students WHERE user_id = $user_id")->fetch_assoc();
$student_number = $student['student_number'];

// 1️⃣ Get LATEST approved enrollment
$enrollment = $conn->query("
    SELECT * FROM enrollments
    WHERE student_number = '$student_number'
      AND status = 'Approved'
    ORDER BY created_at DESC
    LIMIT 1
")->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>Your Class Schedule</title>
    <link rel="stylesheet" href="styles/schedules.css">
    <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
</head>
<body>

<aside class="sidebar">
        <div class="ascot">
            <div class="logo">
                <img src="assets/ASCOT LOGO.png" alt="logo" class="ascot-logo">
                <span>Aurora State College Of Technology</span>
            </div>
        </div>
        <nav>
            <a href="studentDashboard.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/home_1_1.svg" class="icon-logo"><span>Home</span></a>
            <a href="schedules.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/calendar.svg" class="icon-logo"><span>Schedule</span></a>
            <a href="grades.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/paper_3_add.svg" class="icon-logo"><span>Grades</span></a>
            <a href="enrollment.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/profiles_1_add.svg" class="icon-logo"><span>Registration</span></a>
            <a href="studentEdit.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/profile_card.svg" class="icon-logo"><span>Student Information</span></a>
            <a href="logout.php" class="logout"><img src="assets/logout-svgrepo-com.svg" class="icon-logo"><span>Logout</span></a>
        </nav>
        <button><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/menu_1.svg" class="button" id="toggleBtn"></button>
</aside>

<main class="content">
    <h2>Your Class Schedule</h2>

<?php if (!$enrollment): ?>
    <p style="color:red;">No approved enrollment found. Your schedule will appear once admin approves your subjects.</p>
<?php else: ?>

<p><strong>Year Level:</strong> <?= $enrollment['year_level'] ?></p>
<p><strong>Semester:</strong> <?= $enrollment['semester'] ?></p>
<p><strong>Block:</strong> <?= $enrollment['block'] ?></p>

<?php
$enrollment_id = $enrollment['enrollment_id'];

// 2️⃣ Get APPROVED subjects for this enrollment
$subjects = $conn->query("
    SELECT es.subject_id, s.subject_name
    FROM enrollment_subjects es
    JOIN subjects s ON s.subject_id = es.subject_id
    WHERE es.enrollment_id = $enrollment_id
      AND es.status = 'Approved'
");

// 3️⃣ Show schedules
?>

<table border="1" cellpadding="10">
    <tr>
        <th>Subject</th>
        <th>Teacher</th>
        <th>Days & Time</th>
        <th>Room</th>
    </tr>


<?php
if ($subjects->num_rows > 0):

    while ($sub = $subjects->fetch_assoc()):
        $subject_id = $sub['subject_id'];

        // Find class schedule for this subject
        $class = $conn->query("
            SELECT c.*, t.name AS teacher_name
            FROM classes c
            JOIN teachers t ON t.teacher_id = c.teacher_id
            WHERE c.subject_id = $subject_id
              AND c.year_level = {$enrollment['year_level']}
              AND c.semester = '{$enrollment['semester']}'
              AND c.block = '{$enrollment['block']}'
            LIMIT 1
        ")->fetch_assoc();
?>

<tr>
    <td><?= htmlspecialchars($sub['subject_name']) ?></td>
    <td><?= $class ? htmlspecialchars($class['teacher_name']) : 'No assigned teacher' ?></td>
    <td>
        <?= $class ? ($class['days'] ? htmlspecialchars($class['days']) . ' ' : '') . htmlspecialchars($class['schedule_time']) : 'No schedule yet' ?>
    </td>
    <td><?= $class ? htmlspecialchars($class['room']) : 'No room assigned' ?></td>
</tr>

<?php endwhile; ?>


<?php else: ?>
    <tr><td colspan="4">No subjects approved yet.</td></tr>
<?php endif; ?>

</table>

<?php endif; ?>
</main>


<script>
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('expanded');
        });
    </script>
</body>
</html>
