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

// First check if table exists
$tableCheckQuery = "SHOW TABLES LIKE 'users'";
$tableResult = $conn->query($tableCheckQuery);

if ($tableResult->num_rows == 0) {
	die("Users table does not exist!");
}

// Check if columns exist
$checkEmailQuery = "SHOW COLUMNS FROM users LIKE 'email'";
$checkPhoneQuery = "SHOW COLUMNS FROM users LIKE 'phone'";
$emailResult = $conn->query($checkEmailQuery);
$phoneResult = $conn->query($checkPhoneQuery);

if ($emailResult->num_rows == 0 || $phoneResult->num_rows == 0) {
	// Add missing columns
	$alterTableQuery = "ALTER TABLE users";
	if ($emailResult->num_rows == 0) {
		$alterTableQuery .= " ADD COLUMN email VARCHAR(255) DEFAULT NULL";
	}
	if ($phoneResult->num_rows == 0) {
		if ($emailResult->num_rows == 0) {
			$alterTableQuery .= ",";
		}
		$alterTableQuery .= " ADD COLUMN phone VARCHAR(20) DEFAULT NULL";
	}

	if ($conn->query($alterTableQuery) === TRUE) {
		echo "Columns added successfully!";
	} else {
		echo "Error adding columns: " . $conn->error;
	}
} else {
	echo "Required columns already exist!";
}

$conn->close();
?>