<?php
session_start();
include "db_connection.php";

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    // Set 5-minute logout lockout
    $lockout_time = time() + 300; // 5 minutes from now in Unix timestamp
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET logout_lockout_until = FROM_UNIXTIME(?)
        WHERE user_id = ?
    ");
    $stmt->bind_param("ii", $lockout_time, $uid);
    $stmt->execute();
    $stmt->close();
}

session_unset();
session_destroy();

header("Location: index.php");
exit();
?>