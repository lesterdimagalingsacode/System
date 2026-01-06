<?php
session_start();
require_once "db_connection.php";

// 🛑 Only allow logged-in admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: adminLogin.php");
    exit();
}

// 📌 Handle Add Subject
if (isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $description = trim($_POST['description']);
    $year_level = trim($_POST['year_level']);
    $semester = trim($_POST['semester']);

    // ✅ Check for duplicates first
    $check = $conn->prepare("SELECT * FROM subjects WHERE subject_name=? AND year_level=? AND semester=?");
    $check->bind_param("sis", $subject_name, $year_level, $semester);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('This subject already exists for the selected year and semester!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, description, year_level, semester) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $subject_name, $description, $year_level, $semester);
        $stmt->execute();
        echo "<script>alert('Subject added successfully!');</script>";
    }
}

// 📌 Handle Edit Subject
if (isset($_POST['edit_subject'])) {
    $id = $_POST['subject_id'];
    $subject_name = trim($_POST['subject_name']);
    $description = trim($_POST['description']);
    $year_level = trim($_POST['year_level']);
    $semester = trim($_POST['semester']);

    // ✅ Check for duplicates (excluding current subject)
    $check = $conn->prepare("SELECT * FROM subjects WHERE subject_name=? AND year_level=? AND semester=? AND subject_id != ?");
    $check->bind_param("sisi", $subject_name, $year_level, $semester, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Another subject with the same code, year, and semester already exists!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, description=?, year_level=?, semester=? WHERE subject_id=?");
        $stmt->bind_param("ssisi", $subject_name, $description, $year_level, $semester, $id);
        $stmt->execute();
        echo "<script>alert('Subject updated successfully!');</script>";
    }
}

// 📌 Handle Delete Subject
if (isset($_POST['delete_subject'])) {
    $id = $_POST['subject_id'];
    $conn->query("DELETE FROM subjects WHERE subject_id=$id");
    echo "<script>alert('Subject deleted successfully!');</script>";
}

// 📌 Fetch all subjects
$result = $conn->query("SELECT subject_id, subject_name, description, year_level, semester FROM subjects ORDER BY year_level ASC, subject_name ASC");
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
    <title>Manage Subjects</title>
    <style>
    /* Minimal modal & table styling */
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
    .modal-content { background:white; padding:20px; border-radius:8px; width:420px; max-width:90%; }
    .modal-content input, .modal-content textarea, .modal-content select { width:100%; padding:8px; margin:5px 0; }
    .modal-content button { padding:10px 15px; border:none; border-radius:5px; cursor:pointer; display: flex; flex-direction: row; }
    .modal-content .cancel { background:gray; color:white; }
    .btn-primary { background:#37B7C3; color:white; padding:10px 15px; border:none; border-radius:5px; cursor:pointer; }
    table { width:100%; border-collapse:collapse; margin-top:15px; }
    table th, table td { padding:10px; border-bottom:1px solid #ddd; text-align:center; }
    .button-edit{display: flex; flex-direction: row;}
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="ascot">
        <div class="logo">
            <img src="assets/ASCOT LOGO.png" class="ascot-logo">
            <span>Aurora State College Of Technology</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <a href="adminDashboard.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/home_1_1.svg" class="icon-logo"><span>Dashboard</span></a>
        <a href="subjects.php" class="active"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/paper_3_add.svg" class="icon-logo"><span>Manage Subjects</span></a>
        <a href="class_schedules.php"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/calendar.svg" class="icon-logo"><span>Class Scheduling</span></a>
        <a href="pending.php"><img src="assets/mmmicons/png_and_svg (separately)/row_13/svg/mail_2.svg" class="icon-logo"><span>Enrollment</span></a>
        <a href="teacherAccounts.php"><img src="assets/mmmicons/png_and_svg (separately)/row_14/svg/data_1.svg" class="icon-logo"><span>Teacher Accounts</span></a>
        <a href="students.php">
          <img src="assets/mmmicons/png_and_svg (separately)/row_11/svg/profile_card.svg" class="icon-logo">
          <span>Student List</span>
        </a>
        <a href="logout.php" class="logout"><img src="assets/logout-svgrepo-com.svg" class="icon-logo"><span>Logout</span></a>
    </nav>

    <button><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/menu_1.svg" class="button" id="toggleBtn"></button>
</aside>

<!-- MAIN CONTENT -->
<main class="content">
    <h2>Manage Subjects</h2>
    <button onclick="openModal('addModal')" class="btn-primary">➕ Add New Subject</button>

    <table>
        <thead>
            <tr>
                <th>Subject Name</th>
                <th>Description</th>
                <th>Year Level</th>
                <th>Semester</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['subject_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= $row['year_level'] ?></td>
                <td><?= $row['semester'] ?></td>
                <td>
                    <button onclick='editSubject(<?= json_encode($row) ?>)'>✏ Edit</button>
                    <button onclick='deleteSubject(<?= $row["subject_id"] ?>)'>❌ Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>

<!-- ADD SUBJECT MODAL -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Add Subject</h3>
        <form method="POST">
            <input type="text" name="subject_name" placeholder="Subject Code" required>
            <textarea name="description" placeholder="Description"></textarea>
            <select name="year_level" required>
                <option value="">Select Year Level</option>
                <?php for ($i=1; $i<=4; $i++) echo "<option value='$i'>Year $i</option>"; ?>
            </select>
            <select name="semester" required>
                <option value="">Select Semester</option>
                <option value="1st">1st Semester</option>
                <option value="2nd">2nd Semester</option>
                <option value="3rd">3rd Semester</option>
            </select>
            <button type="submit" name="add_subject" class="btn-primary">Add</button>
            <button type="button" class="cancel" onclick="closeModal('addModal')">Cancel</button>
        </form>
    </div>
</div>

<!-- EDIT SUBJECT MODAL -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit Subject</h3>
        <form method="POST">
            <input type="hidden" name="subject_id" id="edit_subject_id">
            <input type="text" name="subject_name" id="edit_subject_name" required>
            <textarea name="description" id="edit_description"></textarea>
            <select name="year_level" id="edit_year" required>
                <?php for ($i=1; $i<=4; $i++) echo "<option value='$i'>Year $i</option>"; ?>
            </select>
            <select name="semester" id="edit_semester" required>
                <option value="">Select Semester</option>
                <option value="1st">1st Semester</option>
                <option value="2nd">2nd Semester</option>
                <option value="3rd">3rd Semester</option>
            </select>
            <div class="button-edit">
              <button type="submit" name="edit_subject" class="btn-primary">Update</button>
              <button type="button" class="cancel" onclick="closeModal('editModal')">Cancel</button>  
            </div>
            
        </form>
    </div>
</div>

<!-- DELETE SUBJECT MODAL -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <h3>Are you sure you want to delete this subject?</h3>
        <form method="POST">
            <input type="hidden" name="subject_id" id="delete_subject_id">
            <button type="submit" name="delete_subject" class="btn-primary">Yes, Delete</button>
            <button type="button" class="cancel" onclick="closeModal('deleteModal')">Cancel</button>
        </form>
    </div>
</div>

<script>
document.getElementById('toggleBtn').onclick = () => {
    document.querySelector('.sidebar').classList.toggle('expanded');
};

function openModal(id){ document.getElementById(id).style.display="flex"; }
function closeModal(id){ document.getElementById(id).style.display="none"; }

function editSubject(data){
    document.getElementById('edit_subject_id').value = data.subject_id;
    document.getElementById('edit_subject_name').value = data.subject_name;
    document.getElementById('edit_description').value = data.description;
    document.getElementById('edit_year').value = data.year_level;
    document.getElementById('edit_semester').value = data.semester;
    openModal('editModal');
}

function deleteSubject(id){
    document.getElementById('delete_subject_id').value = id;
    openModal('deleteModal');
}
</script>
</body>
</html>
