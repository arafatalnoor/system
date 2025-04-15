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

// First, check if the table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
if ($tableCheck->num_rows > 0) {
	// Table exists, alter it to add missing columns
	$alterQuery = "ALTER TABLE users 
				   ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL,
				   ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL,
				   ADD COLUMN IF NOT EXISTS picture VARCHAR(255) DEFAULT NULL,
				   ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
	
	if ($conn->query($alterQuery) === TRUE) {
		echo "Table structure updated successfully";
	} else {
		echo "Error updating table structure: " . $conn->error;
	}
} else {
	// Create new table with all columns
	$createTableQuery = "CREATE TABLE users (
		id INT AUTO_INCREMENT PRIMARY KEY,
		matric VARCHAR(20) NOT NULL UNIQUE,
		name VARCHAR(100) NOT NULL,
		password VARCHAR(255) NOT NULL,
		email VARCHAR(255) DEFAULT NULL,
		phone VARCHAR(20) DEFAULT NULL,
		picture VARCHAR(255) DEFAULT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	)";
	
	if ($conn->query($createTableQuery) === TRUE) {
		echo "Table created successfully";
	} else {
		echo "Error creating table: " . $conn->error;
	}
}

$conn->close();
?>
