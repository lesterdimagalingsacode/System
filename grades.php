<?php
session_start();
require_once "db_connection.php";

// ----------------- ACCESS CONTROL -----------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student info
$student = $conn->query("
    SELECT * FROM students WHERE user_id = $user_id
")->fetch_assoc();

$student_number = $student['student_number'];

// ----------------- FILTER HANDLING -----------------
$selected_year = isset($_GET['year_level']) ? $_GET['year_level'] : "";
$selected_sem = isset($_GET['semester']) ? $_GET['semester'] : "";

// Fetch available year levels + semesters from enrollments
$year_levels = $conn->query("
    SELECT DISTINCT year_level FROM enrollments
    WHERE student_number = '$student_number'
");

$semesters = $conn->query("
    SELECT DISTINCT semester FROM enrollments
    WHERE student_number = '$student_number'
");

// ----------------- FETCH GRADES -----------------
$grades = [];

$grades = [];

if ($selected_year !== "" && $selected_sem !== "") {

    $stmt = $conn->prepare("
        SELECT 
            s.subject_name,
            g.prelim,
            g.midterm,
            g.final,
            g.average,
            g.remarks
        FROM grades g
        JOIN classes c ON g.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        WHERE 
            g.student_id = ?
            AND c.year_level = ?
            AND c.semester = ?
        ORDER BY s.subject_name ASC
    ");

    $stmt->bind_param("iis", $student['student_id'], $selected_year, $selected_sem);
    $stmt->execute();
    $grades = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/grades.css">
    <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
    <title>My Grades</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        th { background: #f0f0f0; }
    </style>
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
    <h2>Welcome, <?= htmlspecialchars($student['name']) ?></h2>
<h3>Your Grades</h3>

<!-- ---------------- Dropdown Form ---------------- -->
<form method="GET">
    <label>Year Level:</label>
    <select name="year_level" required>
        <option value="">-- Select Year Level --</option>
        <?php while ($row = $year_levels->fetch_assoc()): ?>
            <option value="<?= $row['year_level'] ?>"
                <?= $selected_year == $row['year_level'] ? "selected" : "" ?>>
                <?= $row['year_level'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Semester:</label>
    <select name="semester" required>
        <option value="">-- Select Semester --</option>
        <?php while ($row = $semesters->fetch_assoc()): ?>
            <option value="<?= $row['semester'] ?>"
                <?= $selected_sem == $row['semester'] ? "selected" : "" ?>>
                <?= $row['semester'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Filter</button>
</form>

<!-- ---------------- Grades Table ---------------- -->
<?php if ($selected_year !== "" && $selected_sem !== ""): ?>

    <?php if ($grades->num_rows > 0): ?>

        <table>
            <tr>
                <th>Subject</th>
                <th>Prelim</th>
                <th>Midterm</th>
                <th>Final</th>
                <th>Average</th>
                <th>Remarks</th>
            </tr>

            <?php while ($g = $grades->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($g['subject_name']) ?></td>
                    <td><?= $g['prelim'] ?></td>
                    <td><?= $g['midterm'] ?></td>
                    <td><?= $g['final'] ?></td>
                    <td><?= $g['average'] ?></td>
                    <td><?= $g['remarks'] ?></td>
                </tr>
            <?php endwhile; ?>

        </table>

    <?php else: ?>
        <p><strong>No grades found for this semester.</strong></p>
    <?php endif; ?>

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
