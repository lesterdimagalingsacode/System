<?php
session_start();
require_once "db_connection.php";

// Configuration
define('MAX_ATTEMPTS', 3); // Maximum failed attempts before lockout
define('LOCKOUT_TIME', 600); // Lockout duration in seconds (10 minutes)

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Check for logout lockout first
    $logout_check = $conn->prepare("SELECT UNIX_TIMESTAMP(logout_lockout_until) as lockout_unix 
                                     FROM users 
                                     WHERE email = ? AND role = 'teacher'");
    $logout_check->bind_param("s", $email);
    $logout_check->execute();
    $logout_result = $logout_check->get_result();
    
    if ($logout_result->num_rows == 1) {
        $logout_data = $logout_result->fetch_assoc();
        $lockout_unix = $logout_data['lockout_unix'];
        
        if ($lockout_unix !== null && $lockout_unix > time()) {
            $remaining_time = $lockout_unix - time();
            $minutes = ceil($remaining_time / 60);
            $seconds = $remaining_time % 60;
            
            if ($minutes > 0) {
                $time_message = $minutes . " minute(s)";
                if ($seconds > 0 && $minutes < 2) {
                    $time_message .= " and " . $seconds . " second(s)";
                }
            } else {
                $time_message = $seconds . " second(s)";
            }
            
            echo "<script>alert('You recently logged out. Please wait " . $time_message . " before logging in again.');</script>";
            $logout_check->close();
            goto display_form;
        }
    }
    $logout_check->close();

    // Check if account is locked out
    $lockout_check = $conn->prepare("SELECT failed_attempts, UNIX_TIMESTAMP(last_failed_attempt) as last_attempt_unix 
                                      FROM users 
                                      WHERE email = ? AND role = 'teacher'");
    $lockout_check->bind_param("s", $email);
    $lockout_check->execute();
    $lockout_result = $lockout_check->get_result();
    
    if ($lockout_result->num_rows == 1) {
        $lockout_data = $lockout_result->fetch_assoc();
        $failed_attempts = intval($lockout_data['failed_attempts']);
        $last_attempt_unix = $lockout_data['last_attempt_unix'];
        
        // Check if account is currently locked
        if ($failed_attempts >= MAX_ATTEMPTS && $last_attempt_unix !== null) {
            $current_time = time();
            $time_since_last_attempt = $current_time - $last_attempt_unix;
            
            if ($time_since_last_attempt < LOCKOUT_TIME) {
                $remaining_time = LOCKOUT_TIME - $time_since_last_attempt;
                $minutes = ceil($remaining_time / 60);
                $seconds = $remaining_time % 60;
                
                if ($minutes > 0) {
                    $time_message = $minutes . " minute(s)";
                    if ($seconds > 0 && $minutes < 2) {
                        $time_message .= " and " . $seconds . " second(s)";
                    }
                } else {
                    $time_message = $seconds . " second(s)";
                }
                
                echo "<script>alert('Account temporarily locked due to multiple failed login attempts. Please try again in " . $time_message . ".');</script>";
                $lockout_check->close();
                goto display_form;
            } else {
                // Lockout period expired, reset failed attempts
                $reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE email = ?");
                $reset_stmt->bind_param("s", $email);
                $reset_stmt->execute();
                $reset_stmt->close();
            }
        }
    }
    $lockout_check->close();

    // Get user using email + teacher role
    $stmt = $conn->prepare("
        SELECT user_id, email, password, failed_attempts 
        FROM users 
        WHERE email=? AND role='teacher'
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userResult->num_rows == 1) {

        $user = $userResult->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            // Login successful - reset failed attempts and clear logout lockout
            $reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL, logout_lockout_until = NULL WHERE user_id = ?");
            $reset_stmt->bind_param("i", $user["user_id"]);
            $reset_stmt->execute();
            $reset_stmt->close();

            // Fetch teacher info (name, id)
            $stmt2 = $conn->prepare("
                SELECT teacher_id, name 
                FROM teachers 
                WHERE user_id=?
                LIMIT 1
            ");
            $stmt2->bind_param("i", $user["user_id"]);
            $stmt2->execute();
            $teacherResult = $stmt2->get_result();

            if ($teacherResult->num_rows == 1) {

                $teacher = $teacherResult->fetch_assoc();

                // Store important teacher info in SESSION
                $_SESSION["teacher_id"] = $teacher["teacher_id"];
                $_SESSION["teacher_name"] = $teacher["name"];
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["role"] = "teacher";

                header("Location: teacherDashboard.php");
                exit();
            } else {
                echo "<script>alert('Teacher record not found.');</script>";
            }

        } else {
            // Invalid password - increment failed attempts
            $current_attempts = intval($user["failed_attempts"]);
            $new_attempts = $current_attempts + 1;
            $update_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW() WHERE user_id = ?");
            $update_stmt->bind_param("ii", $new_attempts, $user["user_id"]);
            $update_stmt->execute();
            $update_stmt->close();

            $remaining_attempts = MAX_ATTEMPTS - $new_attempts;
            
            if ($new_attempts >= MAX_ATTEMPTS) {
                echo "<script>alert('Invalid password. Your account has been locked for 10 minutes due to multiple failed attempts.');</script>";
            } else if ($remaining_attempts > 0) {
                echo "<script>alert('Incorrect password! You have " . $remaining_attempts . " attempt(s) remaining before your account is temporarily locked.');</script>";
            }
        }

    } else {
        echo "<script>alert('No teacher account found with this email.');</script>";
    }
    $stmt->close();
}

display_form:
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles/logIn.css">
  <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
  <title>Instructor Login</title>
</head>
<body>
  <main class="login-container">
    <div class="title-section">
      <div class="title-content">
        <h1>WELCOME INSTRUCTOR</h1>
      </div>
    </div>
    <div class="form-section">
      <div class="form-container">
        <div class="form-header">
          <svg class="student-icon" height="100px" width="200px" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"></style> <g> <path class="st0" d="M473.61,63.16L276.16,2.927C269.788,0.986,263.004,0,256.001,0c-7.005,0-13.789,0.986-20.161,2.927 L38.386,63.16c-3.457,1.064-5.689,3.509-5.689,6.25c0,2.74,2.232,5.186,5.691,6.25l91.401,27.88v77.228 c0.023,39.93,13.598,78.284,38.224,107.981c11.834,14.254,25.454,25.574,40.483,33.633c15.941,8.564,32.469,12.904,49.124,12.904 c16.646,0,33.176-4.34,49.126-12.904c22.597-12.143,42.04-31.646,56.226-56.39c14.699-25.683,22.471-55.155,22.478-85.224v-78.214 l45.244-13.804v64.192c-6.2,0.784-11.007,6.095-11.007,12.5c0,5.574,3.649,10.404,8.872,12.011l-9.596,63.315 c-0.235,1.576,0.223,3.168,1.262,4.386c1.042,1.204,2.554,1.902,4.148,1.902h36.273c1.592,0,3.104-0.699,4.148-1.91 c1.036-1.203,1.496-2.803,1.262-4.386l-9.596-63.307c5.223-1.607,8.872-6.436,8.872-12.011c0-6.405-4.81-11.716-11.011-12.5V81.544 l19.292-5.885c3.457-1.064,5.691-3.517,5.691-6.25C479.303,66.677,477.069,64.223,473.61,63.16z M257.62,297.871 c-10.413,0-20.994-2.842-31.448-8.455c-16.194-8.649-30.908-23.564-41.438-42.011c-4.854-8.478-8.796-17.702-11.729-27.445 c60.877-10.776,98.51-49.379,119.739-80.97c10.242,20.776,27.661,46.754,54.227,58.648c-3.121,24.984-13.228,48.812-28.532,67.212 c-8.616,10.404-18.773,18.898-29.375,24.573C278.606,295.029,268.025,297.871,257.62,297.871z"></path> <path class="st0" d="M373.786,314.23l-1.004-0.629l-110.533,97.274L151.714,313.6l-1.004,0.629 c-36.853,23.036-76.02,85.652-76.02,156.326v0.955l0.846,0.45C76.291,472.365,152.428,512,262.249,512 c109.819,0,185.958-39.635,186.712-40.038l0.846-0.45v-0.955C449.808,399.881,410.639,337.265,373.786,314.23z"></path> </g> </g></svg>
        </div>
        
        <form action="" method="post" class="login-form">
          <div class="input">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-input" placeholder="Enter your Email Address" required>
          </div>
          <div class="input">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-input" placeholder="Enter your Password" required>
          </div>

          <div class="form-options">
<!--
            <label class="remember-me">
              <input type="checkbox" name="remember" id="remember">
              <span class="checkmark"></span>
              <span class="checkbox-label">Remember me</span>
            </label>
            <a href="#" class="forgot-password">Forgot password?</a>
-->
          </div>

          <button type="submit" class="submit-button">Sign In</button>

          <div class="form-footer">
            <p class="signup-text">
              New here? Contact the admin
            </p>
          </div>

        </form>
      </div>
    </div>
  </main>
  
  
    
</body>
</html>