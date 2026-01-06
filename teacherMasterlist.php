<?php
session_start();
require_once "db_connection.php";

// -------------------------
// 🔐 ACCESS CONTROL
// -------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: LoginTeacher.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch teacher info
$teacher = $conn->query("
    SELECT * FROM teachers WHERE user_id = $user_id
")->fetch_assoc();

$teacher_id = $teacher['teacher_id'];

// -------------------------
// 📌 FETCH ALL CLASSES ASSIGNED TO THIS TEACHER
// -------------------------
$classes_query = $conn->query("
    SELECT c.class_id, s.subject_id, s.subject_name, c.year_level, c.block
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE c.teacher_id = $teacher_id
");

// -------------------------
// 📌 IF CLASS IS SELECTED → FETCH MASTERLIST
// -------------------------
$students_list = [];
$student_count = 0;

if (isset($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);

    $stmt = $conn->prepare("
        SELECT 
            st.student_number,
            st.name AS student_name,
            st.year_level,
            st.block
        FROM enrollment_subjects es
        JOIN enrollments e ON es.enrollment_id = e.enrollment_id
        JOIN students st ON e.student_number = st.student_number
        JOIN classes c ON c.subject_id = es.subject_id 
            AND c.year_level = e.year_level 
            AND c.block = e.block
        WHERE es.status = 'approved'
          AND c.class_id = ?
        GROUP BY st.student_number
        ORDER BY st.name ASC
    ");

    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $students_list = $stmt->get_result();
    $student_count = $students_list->num_rows;
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
    <title>Teacher Masterlist - ASCOT</title>
    <link rel="stylesheet" href="styles/teacherMasterlist.css?v=<?= time() ?>">
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
    <h1> Student Masterlist</h1>

    <!-- Class Selection Card -->
    <div class="selection-card">
        <form method="GET">
            <label for="class_id">Select Class:</label>
            <select name="class_id" id="class_id" required>
                <option value="">-- Select a Class --</option>
                <?php 
                $classes_query->data_seek(0); // Reset pointer
                while ($cl = $classes_query->fetch_assoc()): 
                ?>
                    <option value="<?= $cl['class_id'] ?>" 
                        <?= (isset($_GET['class_id']) && $_GET['class_id'] == $cl['class_id']) ? "selected" : "" ?>>
                        <?= htmlspecialchars($cl['subject_id']) ?> - <?= htmlspecialchars($cl['subject_name']) ?> 
                        (Year <?= $cl['year_level'] ?> Block <?= htmlspecialchars($cl['block']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit"> View Students</button>
        </form>
    </div>

    <!-- Student List -->
    <?php if (isset($_GET['class_id'])): ?>
        
        <!-- Stats Card -->
        <?php if ($student_count > 0): ?>
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?= $student_count ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <h2> Enrolled Students</h2>
            
            <?php if ($student_count > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Full Name</th>
                            <th>Year Level</th>
                            <th>Block/Section</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $students_list->data_seek(0); // Reset pointer
                        while ($st = $students_list->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($st['student_number']) ?></td>
                                <td><?= htmlspecialchars($st['student_name']) ?></td>
                                <td>Year <?= htmlspecialchars($st['year_level']) ?></td>
                                <td><?= htmlspecialchars($st['block']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No students enrolled in this class yet.</p>
                </div>
            <?php endif; ?>
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