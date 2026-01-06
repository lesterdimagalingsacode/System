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
$teacher_id = $teacher['teacher_id'];

// Fetch classes assigned to this teacher
$query = "
    SELECT c.*, s.subject_name
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE c.teacher_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

// Map days to numbers for ordering
$dayOrder = ['Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6,'Sunday'=>7];

// Build array of schedules and sort by day & start time
$schedules = [];
while($row = $result->fetch_assoc()) {
    $daysArray = explode(',', $row['days']);
    foreach($daysArray as $day) {
        $schedules[] = [
            'day' => trim($day),
            'day_num' => $dayOrder[trim($day)] ?? 99,
            'time' => $row['schedule_time'],
            'subject' => $row['subject_name'],
            'year_level' => $row['year_level'],
            'semester' => $row['semester'],
            'block' => $row['block'],
            'room' => $row['room']
        ];
    }
}

// Sort schedules by day_num then by start time
usort($schedules, function($a, $b){
    $timeA = strtotime(explode(' - ', $a['time'])[0]);
    $timeB = strtotime(explode(' - ', $b['time'])[0]);
    return ($a['day_num'] <=> $b['day_num']) ?: ($timeA <=> $timeB);
});

// Group schedules by day
$schedulesByDay = [];
foreach($schedules as $sch) {
    $schedulesByDay[$sch['day']][] = $sch;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>Teaching Schedule - ASCOT</title>
    <link rel="stylesheet" href="styles/teacherSchedules.css?v=<?= time() ?>">
    <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
</head>
<body>

<aside class="sidebar">
    <div class="ascot">
        <div class="logo">
            <img src="assets/ASCOT LOGO.png" class="ascot-logo" alt="ASCOT Logo">
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
    <h2>📚 <?= htmlspecialchars($teacher['name']) ?>'s Teaching Schedule</h2>

    <?php if(empty($schedules)): ?>
        <div class="schedule-container">
            <div class="empty-state">
                <p>No classes assigned yet.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="schedule-container">
            <?php 
            // Display schedules grouped by day
            $daysInOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach($daysInOrder as $day): 
                if(!isset($schedulesByDay[$day])) continue;
            ?>
                <div class="day-group">
                    <div class="day-header">
                        <?= $day ?>
                    </div>
                    
                    <?php foreach($schedulesByDay[$day] as $sch): ?>
                        <div class="schedule-card">
                            <div class="time-slot">
                                🕐 <?= htmlspecialchars($sch['time']) ?>
                            </div>
                            
                            <div class="class-info">
                                <div class="subject-name">
                                    <?= htmlspecialchars($sch['subject']) ?>
                                </div>
                                <div class="class-details">
                                    <span class="badge badge-year">
                                        Year <?= htmlspecialchars($sch['year_level']) ?>
                                    </span>
                                    <span class="badge badge-block">
                                        Block <?= htmlspecialchars($sch['block']) ?>
                                    </span>
                                    <span class="badge badge-semester">
                                        <?= htmlspecialchars($sch['semester']) ?> Semester
                                    </span>
                                </div>
                            </div>
                            
                            <div class="room-info">
                                🚪 Room <?= htmlspecialchars($sch['room']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
const sidebar = document.querySelector('.sidebar');
document.getElementById('toggleBtn').addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
});
</script>

</body>
</html>