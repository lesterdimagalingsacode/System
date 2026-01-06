<?php
session_start();
require_once "db_connection.php";

// 🛑 Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: adminLogin.php");
    exit();
}

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fName = trim($_POST['fName']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPass = $_POST['confirmPass'];

    // Basic validation
    if ($password !== $confirmPass) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            // Insert into users table
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'teacher')");
            $stmt->bind_param("ss", $email, $hashedPassword);
            $stmt->execute();
            $user_id = $stmt->insert_id;

            // Insert into teachers table
            $stmt = $conn->prepare("INSERT INTO teachers (user_id, name) VALUES (?, ?)");
            $fullName = $fName . ' ' . $surname;
            $stmt->bind_param("is", $user_id, $fullName);
            $stmt->execute();

            // Redirect to teacherAccounts.php after success
            header("Location: teacherAccounts.php?success=1");
            exit;
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
    <title>Teacher Account Creation</title>
    <link rel="stylesheet" href="styles/studentAdd.css">
    <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
</head>
<body>

<header>
    <div class="logo-osms">
        <div class="ascot-logo">
            <img src="assets/ASCOT LOGO.png" alt="ascot-logo">
        </div>
        <span class="ascot-name">Aurora State College of Technology</span> 
    </div>
</header>

<main>
    <div class="form-wrapper">
        <div class="form-container">
            <div class="form-title">
                <h2>Teacher's Registration</h2>
                <p>Create account to get started</p>
            </div>

            <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>

            <form action="" method="post">
                <div class="student-input">
                    <div class="input-group fName">
                        <label for="first-name">First Name<span class="required">*</span></label>
                        <input type="text" name="fName" id="first-name" placeholder="Enter your first name" required>
                    </div>

                    <div class="input-group sName">
                        <label for="surname">Surname<span class="required">*</span></label>
                        <input type="text" name="surname" id="surname" placeholder="Enter your surname" required>
                    </div>

                    <div class="input-group emailAddress">
                        <label for="email">Email Address<span class="required">*</span></label>
                        <input type="email" name="email" id="email" placeholder="example@ascot.edu.ph" required>
                    </div>

                    <div class="input-group password">
                        <label for="password">Password<span class="required">*</span></label>
                        <input type="password" name="password" id="password" placeholder="Create a strong password" required>
                        <span class="password-hint">At least 8 characters recommended</span>
                    </div>

                    <div class="input-group confirmPass">
                        <label for="confirmPass">Confirm Password<span class="required">*</span></label>
                        <input type="password" name="confirmPass" id="confirmPass" placeholder="Re-enter your password" required>
                    </div>

                    <button type="submit">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</main>
</body>
</html>
