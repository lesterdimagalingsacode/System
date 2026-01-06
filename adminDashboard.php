<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connection.php';

$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];
$totalTeachers = $conn->query("SELECT COUNT(*) AS total FROM teachers")->fetch_assoc()['total'];
$pendingEnrollments = $conn->query("SELECT COUNT(*) AS total FROM enrollments WHERE status = 'pending'")->fetch_assoc()['total'];

// Student Total
$studentQuery = $conn->query("SELECT name, year_level, block FROM students ORDER BY student_id DESC LIMIT 5");
$students = $studentQuery->fetch_all(MYSQLI_ASSOC);

// Teacher's Total
$teacherQuery = $conn->query("SELECT name, department FROM teachers ORDER BY teacher_id DESC LIMIT 5");
$teachers = $teacherQuery->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/adminDashboard.css">
  <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
  <title>DASHBOARD</title>
</head>
<body>
  <aside class="sidebar">
    <div class="ascot">
      <div class="logo">
        <img src="assets/ASCOT LOGO.png" alt="logo" class="ascot-logo">
        <span>Aurora State College Of Technology</span>
      </div>
    </div>
    
    <nav class="sidebar-menu">
      <a href="adminDashboard.php">
          <img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/home_1_1.svg" class="icon-logo">
          <span>Dashboard</span>
      </a>

      <a href="subjects.php">
          <img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/paper_3_add.svg" class="icon-logo">
          <span>Manage Subjects</span>
      </a>

      <a href="class_schedules.php">
          <img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/calendar.svg" class="icon-logo">
          <span>Class Scheduling</span>
      </a>

      <a href="pending.php">
          <img src="assets/mmmicons/png_and_svg (separately)/row_13/svg/mail_2.svg" class="icon-logo">
          <span>Enrollment</span>
      </a>

      <a href="teacherAccounts.php">
          <img src="assets/mmmicons/png_and_svg (separately)/row_14/svg/data_1.svg" class="icon-logo">
          <span>Teacher Accounts</span>
      </a>

      <a href="students.php">
          <img src="assets/mmmicons/png_and_svg (separately)/row_11/svg/profile_card.svg" class="icon-logo">
          <span>Student List</span>
      </a>

      <a href="logout.php" class="logout">
          <img src="assets/logout-svgrepo-com.svg" class="icon-logo">
          <span>Logout</span>
      </a>
    </nav>


    <button><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/menu_1.svg" alt="" class="button" id="toggleBtn"></button>
  </aside>

  <main class="content">
    <div class="admin_Name">
      <h1>HI, KIM LESTER</h1>
    </div>

    <div class="cards">
        <div class="div1">
        <h3>Admin Overview</h3>
        <p>Here you can monitor overall system statistics.</p>
    </div>

    <!-- Students Overview -->
    <div class="div2">
        <h3>Students (Overall)</h3>
        <strong><?php echo $totalStudents; ?></strong>
    </div>

    <!-- Teachers Overview -->
    <div class="div3">
        <h3>Teachers (Overall)</h3>
        <strong><?php echo $totalTeachers; ?></strong>
    </div>

    <!-- Pending Enrollment -->
    <div class="div4">
        <h3>Pending Enrollment</h3>
        <strong><?php echo $pendingEnrollments; ?></strong>
    </div>
        <div class="div5">
            <h3>Recent Students</h3>
            <?php if (!empty($students)) : ?>
                <ul>
                    <?php foreach ($students as $student) : ?>
                        <li><?php echo $student['name']; ?> (Year <?php echo $student['year_level']; ?> - <?php echo $student['block']; ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>No student records.</p>
            <?php endif; ?>
            <a href="students.php" class="view-more">View All →</a>
        </div>

        <!-- Teacher List Preview -->
        <div class="div6">
            <h3>Recent Teachers</h3>
            <?php if (!empty($teachers)) : ?>
                <ul>
                    <?php foreach ($teachers as $t) : ?>
                        <li><?php echo $t['name']; ?> (<?php echo $t['department']; ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>No teacher records.</p>
            <?php endif; ?>
            <a href="teacherAccounts.php" class="view-more">View All →</a>
        </div>
    </div>
  </main>
</body>

<script>
  const sidebar = document.querySelector('.sidebar');
  const toggleBtn = document.getElementById('toggleBtn');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
  });

  // Optional: highlight active page
  const currentPage = window.location.pathname.split("/").pop();
  document.querySelectorAll('nav a').forEach(link => {
    if (link.getAttribute('href') === currentPage) {
      link.classList.add('active');
    }
  });
</script>


</html>