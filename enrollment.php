<?php
session_start();
require_once "db_connection.php";

// Only allow student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Get student info
$user_id = $_SESSION['user_id'];
$student = $conn->query("SELECT * FROM students WHERE user_id=$user_id")->fetch_assoc();

// ======================================================
//  CHECK IF STUDENT HAS APPROVED ENROLLMENT ALREADY
// ======================================================
$checkApproved = $conn->query("
    SELECT * FROM enrollments
    WHERE student_number = '{$student['student_number']}'
      AND status = 'approved'
    LIMIT 1
");

$hasApprovedEnrollment = ($checkApproved->num_rows > 0);

// ======================================================
//  HANDLE ENROLLMENT SUBMISSION (ONLY IF NOT APPROVED)
// ======================================================
if (!$hasApprovedEnrollment && isset($_POST['enroll'])) {

    $year_level = $_POST['year_level'];
    $block = $_POST['block'];
    $semester_num = $_POST['semester'];
    $semester_text = ($semester_num == 1) ? '1st' : (($semester_num == 2) ? '2nd' : '3rd');

    $subject_ids = $_POST['subjects'] ?? [];

    if (!empty($subject_ids)) {

        // Insert enrollment
        $stmt = $conn->prepare("
            INSERT INTO enrollments 
            (student_number, year_level, block, semester, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");

        $student_number = $student['student_number'];
        $stmt->bind_param("siss", $student_number, $year_level, $block, $semester_text);
        $stmt->execute();
        $enrollment_id = $stmt->insert_id;

        // Insert each subject
        $stmt2 = $conn->prepare("
            INSERT INTO enrollment_subjects (enrollment_id, subject_id, status) 
            VALUES (?, ?, 'pending')
        ");

        foreach ($subject_ids as $sub_id) {
            $check = $conn->query("SELECT subject_id FROM subjects WHERE subject_id=$sub_id");
            if ($check->num_rows == 0) continue;

            $stmt2->bind_param("ii", $enrollment_id, $sub_id);
            $stmt2->execute();
        }

        $success = "Enrollment submitted successfully!";
    } else {
        $error = "Please select at least one subject.";
    }
}

// ======================================================
//  FILTER SUBJECTS
// ======================================================
$selected_year = $_POST['year_level'] ?? '';
$selected_block = $_POST['block'] ?? '';
$selected_semester_num = $_POST['semester'] ?? '';
$selected_semester_text = ($selected_semester_num == 1) ? '1st' : (($selected_semester_num == 2) ? '2nd' : '3rd');


$subjects = [];
if ($selected_year && $selected_semester_text && !$hasApprovedEnrollment) {
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE year_level=? AND semester=? ORDER BY subject_name ASC");
    $stmt->bind_param("is", $selected_year, $selected_semester_text);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ======================================================
//  CURRENT ENROLLMENTS
// ======================================================
$current_enrollments = $conn->query("
    SELECT e.enrollment_id, e.year_level, e.block, e.semester, e.status AS enrollment_status, e.created_at,
           GROUP_CONCAT(CONCAT(sb.subject_name,' [',es.status,']') SEPARATOR ', ') AS subjects
    FROM enrollments e
    JOIN enrollment_subjects es ON es.enrollment_id = e.enrollment_id
    JOIN subjects sb ON sb.subject_id = es.subject_id
    WHERE e.student_number = '{$student['student_number']}'
    GROUP BY e.enrollment_id
    ORDER BY e.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="styles/enrollment.css">
        <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
    <title>Student Enrollment - ASCOT</title>
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
    <h2>Student Enrollment</h2>

    <?php if(!empty($success)): ?>
        <div class="alert alert-success">✓ <?= $success ?></div>
    <?php endif; ?>

    <?php if(!empty($error)): ?>
        <div class="alert alert-error">✕ <?= $error ?></div>
    <?php endif; ?>

    <!-- IF APPROVED ENROLLMENT-->
    <?php if ($hasApprovedEnrollment): ?>
        <div class="alert alert-success" style="padding:15px; font-size:16px;">
             You already have an <b>APPROVED enrollment</b>.  
            You cannot enroll again this semester.
        </div>
    <?php else: ?>

        <!-- FILTER FORM -->
        <div class="form-card">
            <h3>Select Year, Block & Semester</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="year_level" required>
                            <option value="">Select Year</option>
                            <?php for($i=1;$i<=4;$i++): ?>
                                <option value="<?= $i ?>" <?= ($selected_year==$i)?'selected':'' ?>>Year <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Block/Section</label>
                        <input type="text" name="block" value="<?= htmlspecialchars($selected_block) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="1" <?= ($selected_semester_num==1)?'selected':'' ?>>1st Semester</option>
                            <option value="2" <?= ($selected_semester_num==2)?'selected':'' ?>>2nd Semester</option>
                            <option value="3" <?= ($selected_semester_num==3)?'selected':'' ?>>3rd Semester</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="filter_subjects" class="btn btn-primary">Show Available Subjects</button>
            </form>
        </div>

        <!-- SUBJECTS -->
        <?php if($subjects): ?>
        <div class="subjects-container">
            <h3>Available Subjects</h3>
            <form method="POST">
                <input type="hidden" name="year_level" value="<?= $selected_year ?>">
                <input type="hidden" name="block" value="<?= htmlspecialchars($selected_block) ?>">
                <input type="hidden" name="semester" value="<?= $selected_semester_num ?>">

                <div class="subjects-grid">
                    <?php foreach($subjects as $sub): ?>
                        <label class="subject-option">
                            <input type="checkbox" name="subjects[]" value="<?= $sub['subject_id'] ?>">
                            <div class="subject-info">
                                <div class="subject-name"><?= htmlspecialchars($sub['subject_name']) ?></div>
                                <div class="subject-description"><?= htmlspecialchars($sub['description']) ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" name="enroll" class="btn">✅ Enroll Selected Subjects</button>
            </form>
        </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- ENROLLMENT HISTORY -->
    <div class="table-container">
        <h3>Your Enrollment History</h3>

        <?php if($current_enrollments): ?>
            <table>
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Block</th>
                        <th>Semester</th>
                        <th>Subjects</th>
                        <th>Status</th>
                        <th>Submitted Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($current_enrollments as $e): ?>
                    <tr>
                        <td><?= $e['year_level'] ?></td>
                        <td><?= htmlspecialchars($e['block']) ?></td>
                        <td><?= $e['semester'] ?></td>
                        <td><?= htmlspecialchars($e['subjects']) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower($e['enrollment_status']) ?>">
                                <?= $e['enrollment_status'] ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($e['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>📭 No enrollments yet.</p>
        <?php endif; ?>
    </div>
</main>

<script>
    const toggleBtn = document.getElementById('toggleBtn');
    document.querySelector('.sidebar').classList.remove('expanded');
    toggleBtn.addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('expanded');
    });
</script>

</body>
</html>
