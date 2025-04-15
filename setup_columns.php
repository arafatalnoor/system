<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'mydb';

$conn = mysqli_connect($host, $user, $password, $dbname);

// Check connection
if (!$conn) {
	die("Connection failed: " . mysqli_connect_error());
}

// Add email and phone columns
$alter_query = "ALTER TABLE users 
				ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL,
				ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL";

if ($conn->query($alter_query) === TRUE) {
	echo "Table modified successfully. Email and phone columns added.";
} else {
	echo "Error modifying table: " . $conn->error;
}

$conn->close();
?>