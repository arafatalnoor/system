<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "mydb");

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Connection failed']));
}

// Check if lecturer already exists
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'lecturer'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Lecturer already exists']));
}

// Process registration
$name = $_POST['name'];
$matric = $_POST['matric'];
$role = $_POST['role'];

$sql = "INSERT INTO users (name, matric, role) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $name, $matric, $role);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}

$conn->close(); 