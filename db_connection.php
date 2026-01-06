<?php

// Database configuration
$servername = "localhost";   // XAMPP default
$username = "root";          // XAMPP default
$password = "";              // XAMPP default
$dbname = "school_system";   // your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");


?>
