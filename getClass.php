<?php
session_start();
include 'db_connection.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get class_id
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing class_id']);
    exit();
}

$class_id = intval($_GET['id']);

// Fetch class info
$stmt = $conn->prepare("
    SELECT * FROM classes
    WHERE class_id = ?
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Class not found']);
    exit();
}

$class = $result->fetch_assoc();

// Return as JSON
header('Content-Type: application/json');
echo json_encode($class);
