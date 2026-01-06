<?php
session_start();
require_once "db_connection.php";

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: adminLogin.php");
    exit();
}

// Approve/Reject all for a specific enrollment
if(isset($_POST['approve_all']) || isset($_POST['reject_all'])){
    $enrollment_id = (int)$_POST['enrollment_id'];

    if(isset($_POST['approve_all'])){
        // Approve enrollment
        $status = 'Approved';
        $conn->query("UPDATE enrollment_subjects SET status='$status' WHERE enrollment_id=$enrollment_id");
        $conn->query("UPDATE enrollments SET status='$status', archived='no' WHERE enrollment_id=$enrollment_id");
    } else {
        // Reject enrollment -> archive it
        $status = 'Rejected';
        $conn->query("UPDATE enrollment_subjects SET status='Archived' WHERE enrollment_id=$enrollment_id");
        $conn->query("UPDATE enrollments SET status='Archived', archived='yes' WHERE enrollment_id=$enrollment_id");
    }
}



// =============================
// RESET ENROLLMENT FOR NEW SEMESTER
// =============================
if (isset($_POST['reset_semester'])) {

    // Archive all approved enrollments
    $conn->query("UPDATE enrollments SET status='Archived' WHERE status='Approved'");

    // Optionally reset subject status also
    $conn->query("UPDATE enrollment_subjects SET status='Archived' WHERE status='Approved'");

    $conn->query("UPDATE enrollments SET archived='yes' WHERE archived='no'");


    echo "<script>alert('All students are now allowed to enroll for the new semester.');</script>";
}


// Fetch pending enrollments
$pending_result = $conn->query("
    SELECT e.enrollment_id, s.student_number, s.name, s.year_level, s.block, u.email
    FROM enrollments e
    JOIN students s ON s.student_number = e.student_number
    JOIN users u ON u.user_id = s.user_id
    WHERE e.status='Pending' AND e.archived='no'
    ORDER BY s.name
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>Pending Enrollments</title>
    <link rel="stylesheet" href="styles/pending.css">
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

      <a href="logout.php" class="logout"><img src="assets/logout-svgrepo-com.svg" class="icon-logo"><span>Logout</span></a>
    </nav>


    <button><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/menu_1.svg" alt="" class="button" id="toggleBtn"></button>
  </aside>
<main class="content">
<h2>📌 Pending Enrollments</h2>

<form method="POST" style="margin-bottom:20px;">
    <button type="submit" name="reset_semester">
        Reset Enrollment for New Semester
    </button>
</form>


<?php if($pending_result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Student Number</th>
            <th>Name</th>
            <th>Year Level</th>
            <th>Block</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $pending_result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['student_number']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= $row['year_level'] ?></td>
            <td><?= htmlspecialchars($row['block']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
                <button onclick="openModal(<?= $row['enrollment_id'] ?>)">View Subjects</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
<p>No pending enrollments.</p>
<?php endif; ?>
</main>

<!-- Modal -->
<div class="modal" id="pendingModal" style="display:none;">
    <div class="modal-content">
        <h3>Enrollment Subjects</h3>
        <div id="modal_subjects_container"></div>
        <form method="POST">
            <input type="hidden" name="enrollment_id" id="modal_enrollment_id">
            <button type="submit" name="approve_all"> Approve All</button>
            <button type="submit" name="reject_all"> Reject All</button>
            <button type="button" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openModal(enrollment_id){
    document.getElementById('modal_enrollment_id').value = enrollment_id;
    fetch('fetch_pending_subjects.php?enrollment_id='+enrollment_id)
        .then(res => res.json())
        .then(data => {
            let html = '<ul>';
            data.forEach(s => {
                html += `<li>${s.subject_name} - ${s.description} [${s.status}]</li>`;
            });
            html += '</ul>';
            document.getElementById('modal_subjects_container').innerHTML = html;
        });
    document.getElementById('pendingModal').style.display = 'flex';
}
function closeModal(){
    document.getElementById('pendingModal').style.display = 'none';
}
const sidebar = document.querySelector('.sidebar');
  const toggleBtn = document.getElementById('toggleBtn');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
  });
</script>
</body>
</html>