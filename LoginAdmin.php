<?php
include 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Get admin user
    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $hashedPassword);
        $stmt->fetch();

        // Ensure $hashedPassword is not null before calling password_verify
        if ($hashedPassword !== null && password_verify($password, $hashedPassword)) {
            // Login successful
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = 'admin';

            header("Location: adminDashboard.php");
            exit();
        } else {
            echo"<script>alert('Invalid password')</script>";
        }
    } else {
       echo"<script>alert('No admin account found with that email.')</script>";
    }

    // Display error
    if (isset($error)) {
        echo "<p style='color:red;'>$error</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles/logIn.css">
  <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
  <title>Student Login</title>
</head>
<body>
  <main class="login-container">
    <div class="title-section">
      <div class="title-content">
        <h1>WELCOME ADMIN</h1>
      </div>
    </div>
    <div class="form-section">
      <div class="form-container">
        <div class="form-header">
          <svg class="student-icon" height="100px" width="200px" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"></style> <g> <path class="st0" d="M473.61,63.16L276.16,2.927C269.788,0.986,263.004,0,256.001,0c-7.005,0-13.789,0.986-20.161,2.927 L38.386,63.16c-3.457,1.064-5.689,3.509-5.689,6.25c0,2.74,2.232,5.186,5.691,6.25l91.401,27.88v77.228 c0.023,39.93,13.598,78.284,38.224,107.981c11.834,14.254,25.454,25.574,40.483,33.633c15.941,8.564,32.469,12.904,49.124,12.904 c16.646,0,33.176-4.34,49.126-12.904c22.597-12.143,42.04-31.646,56.226-56.39c14.699-25.683,22.471-55.155,22.478-85.224v-78.214 l45.244-13.804v64.192c-6.2,0.784-11.007,6.095-11.007,12.5c0,5.574,3.649,10.404,8.872,12.011l-9.596,63.315 c-0.235,1.576,0.223,3.168,1.262,4.386c1.042,1.204,2.554,1.902,4.148,1.902h36.273c1.592,0,3.104-0.699,4.148-1.91 c1.036-1.203,1.496-2.803,1.262-4.386l-9.596-63.307c5.223-1.607,8.872-6.436,8.872-12.011c0-6.405-4.81-11.716-11.011-12.5V81.544 l19.292-5.885c3.457-1.064,5.691-3.517,5.691-6.25C479.303,66.677,477.069,64.223,473.61,63.16z M257.62,297.871 c-10.413,0-20.994-2.842-31.448-8.455c-16.194-8.649-30.908-23.564-41.438-42.011c-4.854-8.478-8.796-17.702-11.729-27.445 c60.877-10.776,98.51-49.379,119.739-80.97c10.242,20.776,27.661,46.754,54.227,58.648c-3.121,24.984-13.228,48.812-28.532,67.212 c-8.616,10.404-18.773,18.898-29.375,24.573C278.606,295.029,268.025,297.871,257.62,297.871z"></path> <path class="st0" d="M373.786,314.23l-1.004-0.629l-110.533,97.274L151.714,313.6l-1.004,0.629 c-36.853,23.036-76.02,85.652-76.02,156.326v0.955l0.846,0.45C76.291,472.365,152.428,512,262.249,512 c109.819,0,185.958-39.635,186.712-40.038l0.846-0.45v-0.955C449.808,399.881,410.639,337.265,373.786,314.23z"></path> </g> </g></svg>
        </div>
        <form action="LoginAdmin.php" method="post" class="login-form">
          <div class="input">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-input" placeholder="Enter your Email Address">
          </div>
          <div class="input">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-input" placeholder="Enter your Password">
          </div>

          <div class="form-options">
          </div>

          <button type="submit" class="submit-button">Sign In</button>

          <div class="form-footer">
          </div>
        </form>
      </div>
    </div>
  </main>
  
  
    
</body>
</html>