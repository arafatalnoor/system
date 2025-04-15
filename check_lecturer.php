<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "mydb");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

// Check if lecturer exists
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'lecturer'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$response = ['lecturerExists' => $row['count'] > 0];
echo json_encode($response);

$conn->close(); 