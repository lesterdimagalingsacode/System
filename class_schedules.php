<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Add Schedule
if (isset($_POST['add'])) {
    $schedule_time = $_POST['start_time'] . ' - ' . $_POST['end_time'];
    $days = isset($_POST['days']) ? implode(',', $_POST['days']) : '';

    $stmt = $conn->prepare("INSERT INTO classes 
        (subject_id, teacher_id, year_level, semester, block, schedule_time, days, room) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "iiisssss",
        $_POST['subject_id'],
        $_POST['teacher_id'],
        $_POST['year_level'],
        $_POST['semester'],
        $_POST['block'],
        $schedule_time,
        $days,
        $_POST['room']
    );
    $msg = $stmt->execute() ? "✔ Class schedule added!" : "❌ Error adding schedule!";
}

// Handle Edit Schedule
if (isset($_POST['edit'])) {
    $schedule_time = $_POST['start_time'] . ' - ' . $_POST['end_time'];
    $days = isset($_POST['days']) ? implode(',', $_POST['days']) : '';

    $stmt = $conn->prepare("UPDATE classes SET subject_id=?, teacher_id=?, year_level=?, semester=?, block=?, schedule_time=?, days=?, room=? WHERE class_id=?");
    $stmt->bind_param(
        "iiisssssi",
        $_POST['subject_id'],
        $_POST['teacher_id'],
        $_POST['year_level'],
        $_POST['semester'],
        $_POST['block'],
        $schedule_time,
        $days,
        $_POST['room'],
        $_POST['class_id']
    );
    $msg = $stmt->execute() ? "✔ Schedule updated!" : "❌ Error updating!";
}

// Delete Schedule
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM classes WHERE class_id=?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    $msg = "Schedule deleted!";
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
    <title>Class Schedules</title>
    <link rel="stylesheet" href="styles/class_schedules.css">
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
<h2>Manage Class Schedules</h2>
<?php if(isset($msg)) echo "<p class='status'>$msg</p>"; ?>

<button onclick="openAddModal()">+ Add Schedule</button>

<table>
<tr>
    <th>ID</th><th>Subject</th><th>Teacher</th><th>Year</th><th>Semester</th><th>Block</th><th>Days & Time</th><th>Room</th><th>Actions</th>
</tr>
<?php
$query = "SELECT c.class_id, s.subject_name, t.name AS teacher_name, c.year_level, c.semester, c.block, c.schedule_time, c.days, c.room 
          FROM classes c
          JOIN subjects s ON c.subject_id=s.subject_id
          JOIN teachers t ON c.teacher_id=t.teacher_id
          ORDER BY c.year_level, c.semester, c.block";
$result = $conn->query($query);
while($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?= $row['class_id'] ?></td>
    <td><?= $row['subject_name'] ?></td>
    <td><?= $row['teacher_name'] ?></td>
    <td><?= $row['year_level'] ?></td>
    <td><?= $row['semester'] ?></td>
    <td><?= $row['block'] ?></td>
    <td><?= $row['days'] ?> <?= $row['schedule_time'] ?></td>
    <td><?= $row['room'] ?></td>
    <td>
        <button onclick="openEditModal(<?= $row['class_id'] ?>)">✏ Edit</button>
        <button onclick="deleteSchedule(<?= $row['class_id'] ?>)">🗑 Delete</button>
    </td>
</tr>
<?php } ?>
</table>

<!-- Add/Edit Modal -->
<div id="scheduleModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h3 id="modalTitle">Add Schedule</h3>
    <form method="post" id="scheduleForm">
      <input type="hidden" name="class_id" id="class_id">

      <label>Year Level</label>
      <select name="year_level" id="year_level" required onchange="loadSubjects()">
          <option value="">Select Year Level</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
      </select>

      <label>Semester</label>
      <select name="semester" id="semester" required onchange="loadSubjects()">
          <option value="">Select Semester</option>
          <option value="1st">1st</option>
          <option value="2nd">2nd</option>
      </select>

      <label>Subject</label>
      <select name="subject_id" id="subject_id" required>
          <option>Select Year & Semester first</option>
      </select>

      <label>Teacher</label>
      <select name="teacher_id" id="teacher_id" required>
        <?php
        $teachers = $conn->query("SELECT * FROM teachers ORDER BY name");
        while($t = $teachers->fetch_assoc()) {
            echo "<option value='{$t['teacher_id']}'>{$t['name']}</option>";
        }
        ?>
      </select>

      <label>Block</label>
      <input type="text" name="block" id="block" required>

      <label>Days</label>
      <select name="days[]" id="days" multiple>
          <option value="Monday">Monday</option>
          <option value="Tuesday">Tuesday</option>
          <option value="Wednesday">Wednesday</option>
          <option value="Thursday">Thursday</option>
          <option value="Friday">Friday</option>
          <option value="Saturday">Saturday</option>
          <option value="Sunday">Sunday</option>
      </select>

      <label>Start Time</label>
      <input type="time" name="start_time" id="start_time" required>

      <label>End Time</label>
      <input type="time" name="end_time" id="end_time" required>

      <label>Room</label>
      <input type="text" name="room" id="room" required>

      <button type="submit" name="add" id="submitBtn">Save</button>
    </form>
  </div>
</div>

<script>
function openAddModal(){
    document.getElementById('modalTitle').textContent = "Add Schedule";
    document.getElementById('scheduleForm').reset();

    // Clear multi-select days
    const options = document.getElementById('days').options;
    for(let i=0; i<options.length; i++) options[i].selected = false;

    // Update submit button for Add
    const btn = document.getElementById('submitBtn');
    btn.name = "add";
    btn.textContent = "Save";

    document.getElementById('scheduleModal').style.display = "flex";
}

function openEditModal(id){
    fetch(`getClass.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = "Edit Schedule";
            document.getElementById('class_id').value = data.class_id;
            document.getElementById('year_level').value = data.year_level;
            document.getElementById('semester').value = data.semester;
            loadSubjects(data.subject_id);
            document.getElementById('teacher_id').value = data.teacher_id;
            document.getElementById('block').value = data.block;

            const times = data.schedule_time.split(' - ');
            document.getElementById('start_time').value = times[0];
            document.getElementById('end_time').value = times[1];

            // Clear all first
            const options = document.getElementById('days').options;
            for(let i=0; i<options.length; i++) options[i].selected = false;

            // Set selected days
            if(data.days){
                let daysArr = data.days.split(',');
                for(let i=0; i<options.length; i++){
                    if(daysArr.includes(options[i].value)){
                        options[i].selected = true;
                    }
                }
            }

            document.getElementById('room').value = data.room;

            // Update submit button for Edit
            const btn = document.getElementById('submitBtn');
            btn.name = "edit";
            btn.textContent = "Update";

            document.getElementById('scheduleModal').style.display = "flex";
        });
}

function closeModal(){ document.getElementById('scheduleModal').style.display = "none"; }
function deleteSchedule(id){ if(confirm("Delete this schedule?")) window.location.href="class_schedules.php?delete="+id; }

// Load subjects dynamically
function loadSubjects(selected=null){
    const year = document.getElementById('year_level').value;
    const semester = document.getElementById('semester').value;
    const subjectSelect = document.getElementById('subject_id');

    if(year && semester){
        fetch(`getSubjects.php?year=${year}&semester=${semester}`)
        .then(res => res.json())
        .then(data => {
            subjectSelect.innerHTML = "";
            data.forEach(s=>{
                let opt = document.createElement('option');
                opt.value = s.subject_id;
                opt.textContent = s.subject_name;
                if(selected && selected==s.subject_id) opt.selected = true;
                subjectSelect.appendChild(opt);
            });
        });
    } else {
        subjectSelect.innerHTML = "<option>Select Year & Semester first</option>";
    }
}
const sidebar = document.querySelector('.sidebar');
  const toggleBtn = document.getElementById('toggleBtn');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
});
</script>

</main>
</body>
</html>
