<?php
session_start();
require_once "db_connection.php";

// 🛑 Only allow logged-in students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current student info
$stmt = $conn->prepare("SELECT s.*, u.email FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Handle form submission
if (isset($_POST['update_student'])) {
    $name = trim($_POST['name']);
    $student_number = trim($_POST['student_number']);
    $year_level = trim($_POST['year_level']);
    $block = trim($_POST['block']);
    $home_address = trim($_POST['home_address']);
    $email = trim($_POST['email']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address.');</script>";
    } else {
        // Check if email already exists for another user
        $check = $conn->prepare("SELECT user_id FROM users WHERE email=? AND user_id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            echo "<script>alert('This email is already in use. Please choose another.');</script>";
        } else {
            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $target_dir = "uploads/profiles/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $file_name = basename($_FILES["profile_picture"]["name"]);
                $target_file = $target_dir . time() . "_" . $file_name;
                move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);
            } else {
                $target_file = $student['profile_picture']; // keep old if not changed
            }

            // Update students table
            $stmt_update = $conn->prepare("UPDATE students SET profile_picture=?, name=?, student_number=?, year_level=?, block=?, home_address=? WHERE user_id=?");
            $stmt_update->bind_param("sssissi", $target_file, $name, $student_number, $year_level, $block, $home_address, $user_id);
            $stmt_update->execute();

            // Update users table for email
            $stmt_email = $conn->prepare("UPDATE users SET email=? WHERE user_id=?");
            $stmt_email->bind_param("si", $email, $user_id);
            $stmt_email->execute();

            echo "<script>alert('Profile updated successfully!'); window.location='studentEdit.php';</script>";
        }
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
        <link rel="stylesheet" href="styles/studentEdit.css">
        <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
        <title>Edit Profile</title>
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
                <a href="studentEdit.php" class="active"><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/profile_card.svg" class="icon-logo"><span>Student Info</span></a>
                <a href="logout.php" class="logout"><img src="assets/logout-svgrepo-com.svg" class="icon-logo"><span>Logout</span></a>
            </nav>

            <button><img src="assets/mmmicons/png_and_svg (separately)/all_icons/svg/menu_1.svg" class="button" id="toggleBtn"></button>
        </aside>

        <main class="content-form">
            <div class="h2andform">
                <div class="h2">
                    <h2 class="profile-editName">Edit Profile</h2>
                </div>
                <div class="fullForm">
                    <form class="edit-profile" method="POST" enctype="multipart/form-data">
                        <div class="edit-form">
                            <?php if(!empty($student['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($student['profile_picture']) ?>" class="profile" alt="Profile Picture">
                            <?php endif; ?>
                        </div>
                        
                        <div class="edit-form">
                            <label>Change Profile Picture:</label>
                            <input class="pictureButton" type="file" name="profile_picture" accept="image/*"> 
                        </div>
                        
                        <div class="edit-form">
                            <label>Full Name:</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                        </div>
                        
                        <div class="edit-form">
                            <label>Student ID:</label>
                            <input type="text" name="student_number" value="<?= htmlspecialchars($student['student_number']) ?>" required>
                        </div>
                        
                        <div class="edit-form">
                            <label>Year Level:</label>
                            <select name="year_level" required>
                                <?php for($i=1;$i<=4;$i++): ?>
                                    <option value="<?= $i ?>" <?= $student['year_level']==$i?'selected':'' ?>>Year <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="edit-form">
                            <label>Section / Block:</label>
                            <input type="text" name="block" value="<?= htmlspecialchars($student['block']) ?>" required>
                        </div>
                        
                        <div class="edit-form">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
                        </div>
                        
                        <div class="edit-form">
                            <label>Home Address:</label>
                            <textarea name="home_address"><?= htmlspecialchars($student['home_address']) ?></textarea>
                        </div>
                        
                        <div class="edit-form">
                            <button class="button" type="submit" name="update_student">Update Profile</button>
                        </div>
                        
                    </form>
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
