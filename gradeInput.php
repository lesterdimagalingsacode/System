<?php
session_start();
require_once "db_connection.php";

// Only teachers can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: LoginTeacher.html");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$user_id = $_SESSION['user_id'];
$success = $error = '';

// Handle form submission
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades']) && isset($_POST['student_id'])) {
    $class_id = $_POST['class_id'];
    $students = $_POST['student_id'];
    $prelims = $_POST['prelim'];
    $midterms = $_POST['midterm'];
    $finals = $_POST['final'];

    $stmt_insert = $conn->prepare("
        INSERT INTO grades (student_id, class_id, prelim, midterm, final, average, remarks, submitted_by, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        prelim=VALUES(prelim),
        midterm=VALUES(midterm),
        final=VALUES(final),
        average=VALUES(average),
        remarks=VALUES(remarks)
    ");

    foreach ($students as $index => $student_id) {
        $prelim = floatval($prelims[$index]);
        $midterm = floatval($midterms[$index]);
        $final = floatval($finals[$index]);

        // Validate grades
        if ($prelim < 0 || $prelim > 100 || $midterm < 0 || $midterm > 100 || $final < 0 || $final > 100) {
            $error = "Grades must be between 0 and 100!";
            break;
        }

        $average = round(($prelim + $midterm + $final)/3, 2);
        $remarks = ($average >= 75) ? 'Passed' : 'Failed';

        $stmt_insert->bind_param("iidddssi", $student_id, $class_id, $prelim, $midterm, $final, $average, $remarks, $teacher_id);
        $stmt_insert->execute();
    }

    if (!$error) $success = "Grades saved successfully!";
}


// Get classes assigned to teacher
$classes = $conn->query("
    SELECT c.class_id, s.subject_name, c.year_level, c.block, c.semester
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    WHERE c.teacher_id = $teacher_id
    ORDER BY c.year_level, c.semester
")->fetch_all(MYSQLI_ASSOC);

// Get selected class and its students
$selected_class_id = $_POST['class_id'] ?? '';
$students_in_class = [];

if ($selected_class_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT st.student_id, st.student_number, st.name
        FROM enrollments e
        JOIN students st ON st.student_number = e.student_number
        JOIN classes c ON c.class_id = ?
        WHERE e.year_level = c.year_level
          AND e.block = c.block
          AND e.semester = c.semester
          AND e.status = 'Approved'
        ORDER BY st.name
    ");
    $stmt->bind_param("i", $selected_class_id);
    $stmt->execute();
    $students_in_class = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get existing grades for selected class
$existing_grades = [];
if ($selected_class_id) {
    $stmt = $conn->prepare("SELECT * FROM grades WHERE class_id=?");
    $stmt->bind_param("i", $selected_class_id);
    $stmt->execute();
    $grades_result = $stmt->get_result();
    while ($row = $grades_result->fetch_assoc()) {
        $existing_grades[$row['student_id']] = $row;
    }
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
    <title>Grade Input - ASCOT</title>
    <link rel="stylesheet" href="styles/gradeInput.css">
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
    <h2>Grade Input</h2>

    <?php if($error): ?>
        <div class="alert alert-error">✕ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Class Selection -->
    <div class="class-selection-card">
        <form method="post">
            <label for="class_id">Select Class:</label>
            <select name="class_id" id="class_id" onchange="this.form.submit()" required>
                <option value="">-- Select a Class --</option>
                <?php foreach($classes as $c): ?>
                    <option value="<?= $c['class_id'] ?>" <?= ($selected_class_id==$c['class_id'])?'selected':'' ?>>
                        <?= htmlspecialchars($c['subject_name']) ?> | Year <?= $c['year_level'] ?> | Block <?= htmlspecialchars($c['block']) ?> | <?= htmlspecialchars($c['semester']) ?> Semester
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Grade Input Table -->
    <?php if($selected_class_id && $students_in_class): ?>
    <div class="grade-table-card">
        <form method="post">
            <input type="hidden" name="class_id" value="<?= $selected_class_id ?>">
            
            <table>
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Student Name</th>
                        <th>Prelim</th>
                        <th>Midterm</th>
                        <th>Final</th>
                        <th>Average</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students_in_class as $s): 
                        $grade = $existing_grades[$s['student_id']] ?? ['prelim'=>0,'midterm'=>0,'final'=>0,'average'=>0,'remarks'=>''];
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($s['student_number']) ?>
                            <input type="hidden" name="student_id[]" value="<?= $s['student_id'] ?>">
                        </td>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><input type="number" name="prelim[]" value="<?= $grade['prelim'] ?>" min="0" max="100" step="0.01" required></td>
                        <td><input type="number" name="midterm[]" value="<?= $grade['midterm'] ?>" min="0" max="100" step="0.01" required></td>
                        <td><input type="number" name="final[]" value="<?= $grade['final'] ?>" min="0" max="100" step="0.01" required></td>
                        <td class="average">-</td>
                        <td class="remarks">-</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" name="submit_grades" class="btn-submit">💾 Save All Grades</button>
        </form>
    </div>
    <?php elseif($selected_class_id): ?>
        <div class="grade-table-card">
            <div class="empty-state"><p>📭 No students enrolled in this class yet.</p></div>
        </div>
    <?php endif; ?>

</main>

<script>
// Auto-calculate average and remarks
document.querySelectorAll('table tbody tr').forEach(row => {
    const prelim = row.querySelector('input[name="prelim[]"]');
    const midterm = row.querySelector('input[name="midterm[]"]');
    const finalInput = row.querySelector('input[name="final[]"]');
    const avgCell = row.querySelector('.average');
    const remarksCell = row.querySelector('.remarks');

    function calculate() {
        const p = parseFloat(prelim.value) || 0;
        const m = parseFloat(midterm.value) || 0;
        const f = parseFloat(finalInput.value) || 0;
        const avg = ((p + m + f)/3).toFixed(2);
        avgCell.textContent = avg;
        remarksCell.textContent = avg >= 75 ? 'Passed' : 'Failed';
        remarksCell.style.color = avg >= 75 ? '#28a745' : '#dc3545';
    }

    prelim.addEventListener('input', calculate);
    midterm.addEventListener('input', calculate);
    finalInput.addEventListener('input', calculate);

    calculate(); // initial calculation
});

// Sidebar toggle
const sidebar = document.querySelector('.sidebar');
document.getElementById('toggleBtn').addEventListener('click', () => sidebar.classList.toggle('expanded'));
</script>

</body>
</html>
