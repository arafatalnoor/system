<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['matric']) || !isset($_SESSION['role'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if the role is student
if ($_SESSION['role'] !== 'student') {
    header("Location: dashboard.php");
    exit();
}

$matric = $_SESSION['matric'];

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'mydb';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch user data
$sql = "SELECT name, picture FROM users WHERE matric = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matric);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = htmlspecialchars($user['name']);
    $picture = htmlspecialchars($user['picture'] ?? 'default_profile.png');
} else {
    // If user does not exist, terminate the session
    session_destroy();
    header("Location: index.html");
    exit();
}

$stmt->close();
$conn->close();
?>