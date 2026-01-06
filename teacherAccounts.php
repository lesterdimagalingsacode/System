<?php
session_start();
require_once "db_connection.php";

// 🛑 Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: adminLogin.php");
    exit();
}

$success = $error = '';

// Handle edit/delete submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'edit') {
        $teacher_id = $_POST['teacher_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $department = trim($_POST['department'] ?? '');
        $profile_info = trim($_POST['profile_info'] ?? '');

        // Update teacher info
        $stmt = $conn->prepare("UPDATE teachers t JOIN users u ON t.user_id=u.user_id SET t.name=?, t.department=?, t.profile_info=?, u.email=? WHERE t.teachers_id=?");
        $stmt->bind_param("ssssi", $name, $department, $profile_info, $email, $teacher_id);
        $stmt->execute();

        $success = "Teacher info updated successfully!";
    } elseif ($action === 'delete') {
        $teacher_id = $_POST['teacher_id'];
        // Delete teacher and corresponding user
        $stmt = $conn->prepare("SELECT user_id FROM teachers WHERE teacher_id=?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $user_id = $stmt->get_result()->fetch_assoc()['user_id'];

        $stmt = $conn->prepare("DELETE FROM teachers WHERE teacher_id=?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $success = "Teacher account deleted successfully!";
    }
}

// Fetch all teachers
$teachers = $conn->query("
    SELECT t.teacher_id, t.name, t.department, t.profile_info, u.email 
    FROM teachers t
    JOIN users u ON t.user_id = u.user_id
    ORDER BY t.name ASC
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
    <title>Teacher Accounts</title>
    <link rel="stylesheet" href="styles/teacherAccounts.css">
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

<main>
    <h2>Teacher Accounts</h2>
    <?php if($success) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>

    <?php if($teachers): ?>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Profile Info</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($teachers as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['name']) ?></td>
                        <td><?= htmlspecialchars($t['email']) ?></td>
                        <td><?= htmlspecialchars($t['department']) ?></td>
                        <td><?= htmlspecialchars($t['profile_info']) ?></td>
                        <td>
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($t)) ?>)">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="teacher_id" value="<?= $t['teacher_id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" onclick="return confirm('Delete this teacher?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="teacherAdd.php"><button style="margin-top:10px;">Add New Teacher</button></a>
    <?php else: ?>
        <p>No teachers found.</p>
        <a href="teacherAdd.php"><button>Add Teacher</button></a>
    <?php endif; ?>

    <!-- Edit Form Modal -->
    <div id="teacherFormModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
        <div style="background:#fff; padding:20px; max-width:500px; margin:50px auto; position:relative;">
            <h3 id="formTitle">Edit Teacher</h3>
            <form method="POST" id="teacherForm">
                <input type="hidden" name="action" id="formAction" value="edit">
                <input type="hidden" name="teacher_id" id="teacherId">

                <label>Full Name*</label>
                <input type="text" name="name" id="teacherName" required>

                <label>Email*</label>
                <input type="email" name="email" id="teacherEmail" required>

                <label>Department</label>
                <input type="text" name="department" id="teacherDepartment">

                <label>Profile Info</label>
                <textarea name="profile_info" id="teacherProfileInfo"></textarea>

                <button type="submit">Save</button>
                <button type="button" onclick="closeForm()">Cancel</button>
            </form>
        </div>
    </div>

</main>

<script>
function openEditModal(data){
    document.getElementById('teacherId').value = data.teacher_id;
    document.getElementById('teacherName').value = data.name;
    document.getElementById('teacherEmail').value = data.email;
    document.getElementById('teacherDepartment').value = data.department;
    document.getElementById('teacherProfileInfo').value = data.profile_info;
    document.getElementById('teacherFormModal').style.display = 'block';
}

function closeForm(){
    document.getElementById('teacherFormModal').style.display = 'none';
}

const sidebar = document.querySelector('.sidebar');
  const toggleBtn = document.getElementById('toggleBtn');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
  });
</script>

</body>
</html>
