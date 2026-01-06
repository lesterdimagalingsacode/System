<?php
session_start();
require_once "db_connection.php";

// Only allow logged-in students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student info
$stmt = $conn->prepare("SELECT s.*, u.email FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

$student_number = $student['student_number'] ?? null;

// Get latest APPROVED enrollment for this student (if any)
$latestEnrollment = null;
if ($student_number) {
    $stmt = $conn->prepare("
        SELECT * FROM enrollments
        WHERE student_number = ?
          AND status = 'Approved'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $student_number);
    $stmt->execute();
    $latestEnrollment = $stmt->get_result()->fetch_assoc();
}

// If there is an approved enrollment, override displayed year/block/semester
// and optionally update students table to reflect the approved enrollment.
// (If you DON'T want automatic DB update, comment out the UPDATE below.)
if ($latestEnrollment) {
    // Display override values
    $display_year_level = $latestEnrollment['year_level'];
    $display_block = $latestEnrollment['block'];
    $display_semester = $latestEnrollment['semester'];

    // Update students table if stored values differ (keeps main record in sync)
    // NOTE: This performs a DB write — remove if you only want display-only behavior.
    if ($student['year_level'] != $display_year_level || $student['block'] != $display_block) {
        $upd = $conn->prepare("UPDATE students SET year_level = ?, block = ? WHERE student_number = ?");
        $upd->bind_param("sss", $display_year_level, $display_block, $student_number);
        $upd->execute();
        // refresh $student values used later
        $student['year_level'] = $display_year_level;
        $student['block'] = $display_block;
    }
} else {
    // No approved enrollment -> use student's stored values (semester blank)
    $display_year_level = $student['year_level'] ?? '';
    $display_block = $student['block'] ?? '';
    $display_semester = ''; // students table doesn't have semester
}

// DIV3 — Grades summary: check whether student has grades for the approved enrollment classes
$grades_summary = [];
if ($latestEnrollment) {
    // Get class_ids for the student's approved subjects (classes matching year/semester/block & subjects approved)
    // First get approved subject_ids from enrollment_subjects
    $stmt = $conn->prepare("
        SELECT es.subject_id
        FROM enrollment_subjects es
        WHERE es.enrollment_id = ?
          AND es.status = 'Approved'
    ");
    $stmt->bind_param("i", $latestEnrollment['enrollment_id']);
    $stmt->execute();
    $res_subjects = $stmt->get_result();
    $approved_subject_ids = [];
    while ($r = $res_subjects->fetch_assoc()) {
        $approved_subject_ids[] = $r['subject_id'];
    }

    if (!empty($approved_subject_ids)) {
        // Find classes for those subjects that match the enrollment year/block/semester
        $in = implode(',', array_fill(0, count($approved_subject_ids), '?'));
        // We'll build a dynamic prepared statement
        $types = str_repeat('i', count($approved_subject_ids));
        $params = $approved_subject_ids;
        // Query classes
        $sql = "
            SELECT c.class_id, c.subject_id, c.schedule_time, c.days, c.room, t.name AS teacher_name, s.subject_name
            FROM classes c
            JOIN teachers t ON c.teacher_id = t.teacher_id
            JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.subject_id IN ($in)
              AND c.year_level = ?
              AND c.block = ?
              AND c.semester = ?
        ";
        $stmt = $conn->prepare($sql);

        // bind params dynamically
        // build array of references
        $bind_names = [];
        $i = 0;
        $param_refs = [];
        foreach ($params as $p) {
            $param_refs[] = $p;
        }
        // then add year_level, block, semester
        $param_refs[] = $display_year_level;
        $param_refs[] = $display_block;
        $param_refs[] = $display_semester;

        // build types string
        $bind_types = $types . 'sss';

        // call_user_func_array requires references
        $stmt_params = [];
        $stmt_params[] = & $bind_types;
        foreach ($param_refs as $key => $value) {
            $stmt_params[] = & $param_refs[$key];
        }

        // Use a helper to bind dynamic params
        call_user_func_array([$stmt, 'bind_param'], $stmt_params);

        $stmt->execute();
        $classes_result = $stmt->get_result();

        $classes_for_student = [];
        while ($c = $classes_result->fetch_assoc()) {
            $classes_for_student[] = $c;
        }

        // For grades summary: check grades table for this student_number and the class_ids found
        $class_ids = array_column($classes_for_student, 'class_id');
        if (!empty($class_ids)) {
            $in2 = implode(',', array_fill(0, count($class_ids), '?'));
            $types2 = str_repeat('i', count($class_ids));
            $sql2 = "SELECT * FROM grades WHERE student_id = ? AND class_id IN ($in2)";
            $stmt2 = $conn->prepare($sql2);
            // bind params: first student_number (string), then class_ids ints
            $bind_types2 = 's' . $types2;
            $param_refs2 = [];
            $param_refs2[] = & $bind_types2;
            $param_refs2[] = & $student_number;
            foreach ($class_ids as $k => $cid) {
                $param_refs2[] = & $class_ids[$k];
            }
            call_user_func_array([$stmt2, 'bind_param'], $param_refs2);
            $stmt2->execute();
            $grades_res = $stmt2->get_result();
            $grades_list = $grades_res->fetch_all(MYSQLI_ASSOC);

            $grades_summary['count'] = count($grades_list);
            $grades_summary['details'] = $grades_list;
            $grades_summary['classes'] = $classes_for_student;
        } else {
            $grades_summary['count'] = 0;
            $grades_summary['details'] = [];
            $grades_summary['classes'] = $classes_for_student;
        }
    } else {
        $grades_summary['count'] = 0;
        $grades_summary['details'] = [];
        $grades_summary['classes'] = [];
    }
} else {
    $grades_summary['count'] = 0;
    $grades_summary['details'] = [];
    $grades_summary['classes'] = [];
}

// DIV4 — Schedules: show schedules for the approved subjects (split days/time per line)
$schedules_list = [];
if (!empty($classes_for_student)) {
    foreach ($classes_for_student as $cl) {
        // explode days by comma
        $days = $cl['days'] ? explode(',', $cl['days']) : [];
        if (empty($days)) {
            $schedules_list[] = [
                'subject' => $cl['subject_name'],
                'teacher' => $cl['teacher_name'],
                'times' => [($cl['schedule_time'] ?? 'No time')],
                'room' => $cl['room']
            ];
        } else {
            foreach ($days as $d) {
                $schedules_list[] = [
                    'subject' => $cl['subject_name'],
                    'teacher' => $cl['teacher_name'],
                    'times' => [$d . ' ' . ($cl['schedule_time'] ?? '')],
                    'room' => $cl['room']
                ];
            }
        }
    }
}

// DIV5 — Registration status (show latest enrollment status & applied date)
$registration_info = [];
if ($latestEnrollment) {
    $registration_info['status'] = $latestEnrollment['status'];
    $registration_info['applied_at'] = $latestEnrollment['created_at'];
    $registration_info['year_level'] = $latestEnrollment['year_level'];
    $registration_info['block'] = $latestEnrollment['block'];
    $registration_info['semester'] = $latestEnrollment['semester'];
} else {
    $registration_info['status'] = 'No enrollment';
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
    <link rel="stylesheet" href="styles/studentDashboard.css">
    <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
    <title>Student Dashboard</title>
</head>
<body>
    <!-- Sidebar -->
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

    <!-- Main Content -->
    <main class="content">
        <div>
            <h1>HI, <?= htmlspecialchars($student['name']) ?></h1>
        </div>

        <div class="cards">
            <!-- DIV1 -->
            <div class="div1">
                <h3>Student’s Overview</h3>
                <p><strong>Student #:</strong> <?= htmlspecialchars($student_number) ?></p>

            </div>

            <!-- STUDENT INFORMATION CARD (DIV2) -->
            <div class="div2">
                <h3>Student Information</h3>
                <div class="student-info-card">
                    <div class="profile-pic-container">
                        <?php if(!empty($student['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($student['profile_picture']) ?>" alt="Profile Picture" class="profile">
                        <?php else: ?>
                            <img src="assets/default-profile.png" alt="Default Profile" class="profile">
                        <?php endif; ?>
                    </div>

                    <div class="info-container">
                        <div class="info-row"><span class="label">Name:</span><span class="value"><?= htmlspecialchars($student['name']) ?></span></div>
                        <div class="info-row"><span class="label">Student ID:</span><span class="value"><?= htmlspecialchars($student['student_number']) ?></span></div>
                        <div class="info-row"><span class="label">Year Level:</span><span class="value"><?= htmlspecialchars($display_year_level) ?></span></div>
                        <div class="info-row"><span class="label">Semester:</span><span class="value"><?= htmlspecialchars($display_semester ?: '—') ?></span></div>
                        <div class="info-row"><span class="label">Section / Block:</span><span class="value"><?= htmlspecialchars($display_block) ?></span></div>
                        <div class="info-row"><span class="label">Email:</span><span class="value"><?= htmlspecialchars($student['email']) ?></span></div>
                        <div class="info-row"><span class="label">Home Address:</span><span class="value"><?= htmlspecialchars($student['home_address']) ?></span></div>

                        <div class="edit-button-container">
                            <a href="studentEdit.php" class="btn-edit">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DIV3: Grades -->
            <div class="div3">
                <h3>Your Grades</h3>
                <?php if ($latestEnrollment): ?>
                    <p>Enrollment (<?= htmlspecialchars($latestEnrollment['year_level']) ?> / <?= htmlspecialchars($latestEnrollment['block']) ?> — <?= htmlspecialchars($latestEnrollment['semester']) ?>): <strong><?= htmlspecialchars($latestEnrollment['status']) ?></strong></p>
                    <p>Saved grades for this enrollment: <strong><?= intval($grades_summary['count']) ?></strong></p>

                    <?php if (!empty($grades_summary['details'])): ?>
                        <ul>
                        <?php foreach ($grades_summary['details'] as $g): ?>
                            <li>
                                Class ID <?= htmlspecialchars($g['class_id']) ?> — Average: <?= htmlspecialchars($g['average']) ?> — Remarks: <?= htmlspecialchars($g['remarks']) ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                        <p><a href="grades.php">View full grades</a></p>
                    <?php else: ?>
                        <p>No grades entered yet for your approved subjects. Check back after your teacher inputs them.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>You have no approved enrollment yet. Grades will show here after approval and teacher input.</p>
                <?php endif; ?>
            </div>

            <!-- DIV4: Schedules -->
            <div class="div4">
                <h3>Your Schedules</h3>
                <?php if (!empty($schedules_list)): ?>
                    <ul>
                        <?php foreach ($schedules_list as $s): ?>
                            <li>
                                <strong><?= htmlspecialchars($s['subject']) ?></strong>
                                — <?= htmlspecialchars($s['teacher']) ?><br>
                                <?php foreach ($s['times'] as $tt): ?>
                                    <span><?= htmlspecialchars($tt) ?></span>
                                <?php endforeach; ?>
                                <br><em>Room: <?= htmlspecialchars($s['room']) ?></em>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p><a href="schedules.php">View full schedule</a></p>
                <?php else: ?>
                    <p>No class schedules found for your approved subjects yet.</p>
                <?php endif; ?>
            </div>

            <!-- DIV5: Registration -->
            <div class="div5">
                <h3>Registration</h3>
                <?php if ($latestEnrollment): ?>
                    <p><strong>Status:</strong> <?= htmlspecialchars($registration_info['status']) ?></p>
                    <p><strong>Applied at:</strong> <?= htmlspecialchars(date('M d, Y H:i', strtotime($registration_info['applied_at']))) ?></p>
                    <p><strong>For:</strong> Year <?= htmlspecialchars($registration_info['year_level']) ?> / Block <?= htmlspecialchars($registration_info['block']) ?> — <?= htmlspecialchars($registration_info['semester']) ?></p>
                    <p><a href="enrollment.php">View enrollment details</a></p>
                <?php else: ?>
                    <p>You have no enrollment application yet. <a href="enrollment.php">Register here</a>.</p>
                <?php endif; ?>
            </div>
        </div>
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
