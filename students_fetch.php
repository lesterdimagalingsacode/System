<?php
require_once "db_connection.php";

// Search input
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Query based ONLY on your existing columns
$sql = "
    SELECT *
    FROM students
    WHERE 
        name LIKE '%$search%' OR
        student_number LIKE '%$search%' OR
        year_level LIKE '%$search%' OR
        block LIKE '%$search%' OR
        email LIKE '%$search%' OR
        home_address LIKE '%$search%'
";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
