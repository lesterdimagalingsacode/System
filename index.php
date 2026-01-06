<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['roles'])) {
        $role = $_POST['roles'];

        // Redirect based on role selection
        if ($role == "Student") {
            header("Location: LoginStudent.php");
            exit();
        } elseif ($role == "Teacher") {
            header("Location: LoginTeacher.php");
            exit();
        } elseif ($role == "Admin") {
            header("Location: LoginAdmin.php");
            exit();
        }
        exit();
    } else {
        $error = "Please select a role before continuing.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select Roles</title>
  <link rel="stylesheet" href="styles/roles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="shortcut icon" href="assets/ASCOT LOGO.png" type="image/x-icon">
</head>
<body>
  <form class="form-container" action="index.php" method="post">
    <div class="form-header">
      <h2>Select Your Role</h2>
      <p>Choose the option that best describes you</p>
    </div>

    <div class="options-container">
      <label for="student" class="option">
        <div class="option-content">
          
          <span class="option-label">Student</span>
        </div>
        <div class="radio-wrapper">
          <input type="radio" name="roles" id="student" value="Student">
        </div>
      </label> 

      <label for="teacher" class="option">
        <div class="option-content">
          
          <span class="option-label">Teacher</span>
        </div>
        <div class="radio-wrapper">
          <input type="radio" name="roles" id="teacher" value="Teacher">
        </div>
      </label>

      <label for="admin" class="option">
        <div class="option-content">
          
          <span class="option-label">Administrator</span>
        </div>
        <div class="radio-wrapper">
          <input type="radio" name="roles" id="admin" value="Admin">
        </div>
      </label>
    </div>
    
    <button type="submit" class="submit-button">Continue</button>
  </form>

  <script>
    // Add form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const selectedRole = document.querySelector('input[name="roles"]:checked');
      
      if (!selectedRole) {
        e.preventDefault();
        alert('Please select a role before continuing.');
        return;
      }
      
      // Optional: Add loading state to button
      const submitBtn = document.querySelector('.submit-button');
      submitBtn.textContent = 'Processing...';
      submitBtn.disabled = true;
    });

    // Add keyboard navigation enhancement
    document.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        const radios = document.querySelectorAll('input[type="radio"]');
        const currentIndex = Array.from(radios).findIndex(radio => radio === document.activeElement);
        
        if (currentIndex !== -1) {
          e.preventDefault();
          let nextIndex;
          
          if (e.key === 'ArrowDown') {
            nextIndex = (currentIndex + 1) % radios.length;
          } else {
            nextIndex = currentIndex === 0 ? radios.length - 1 : currentIndex - 1;
          }
          
          radios[nextIndex].focus();
          radios[nextIndex].checked = true;
        }
      }
    });
  </script>
</body>
</html>