<?php
require_once "db_connection.php";

if (!isset($_GET['enrollment_id'])) {
    echo json_encode([]);
    exit();
}

$enrollment_id = (int)$_GET['enrollment_id'];

// Fetch subjects under this enrollment
$sql = "
    SELECT 
        es.status,
        sj.subject_name,
        sj.description
    FROM enrollment_subjects es
    JOIN subjects sj ON sj.subject_id = es.subject_id
    WHERE es.enrollment_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $enrollment_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

echo json_encode($subjects);
?>
