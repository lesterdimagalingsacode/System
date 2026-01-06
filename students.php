<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
    <link rel="stylesheet" href="styles/students.css">
    <title>Students List</title>
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
        <h2>All Students</h2>

    <input type="text" id="search" placeholder="Search students...">

    <table>
        <thead>
            <tr>
                <th>Profile</th>
                <th>Student Number</th>
                <th>Name</th>
                <th>Year</th>
                <th>Block</th>
                <th>Email</th>
                <th>Address</th>
            </tr>
        </thead>
        <tbody id="students_table"></tbody>
    </table>
  </main>


<script>
function loadStudents(query = "") {
    fetch("students_fetch.php?search=" + query)
    .then(res => res.json())
    .then(data => {
        let rows = "";

        data.forEach(s => {
            rows += `
                <tr>
                    <td><img class="profile" src="${s.profile_picture ? s.profile_picture : 'default.png'}"></td>
                    <td>${s.student_number}</td>
                    <td>${s.name}</td>
                    <td>${s.year_level}</td>
                    <td>${s.block}</td>
                    <td>${s.email}</td>
                    <td>${s.home_address}</td>
                </tr>
            `;
        });

        document.getElementById("students_table").innerHTML = rows;
    });
}

// Load default
loadStudents();

// Live search
document.getElementById("search").addEventListener("keyup", function() {
    loadStudents(this.value);
});

const sidebar = document.querySelector('.sidebar');
  const toggleBtn = document.getElementById('toggleBtn');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
});
</script>

</body>
</html>
