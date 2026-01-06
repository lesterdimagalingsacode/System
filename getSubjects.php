<?php
include 'db_connection.php';
$year = $_GET['year'];
$semester = $_GET['semester'];

$stmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE year_level=? AND semester=? ORDER BY subject_name");
$stmt->bind_param("is", $year, $semester);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while($row = $result->fetch_assoc()) $subjects[] = $row;

echo json_encode($subjects);
