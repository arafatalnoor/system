<?php
// db_connection.php

// Replace with your actual database connection details
$servername = "localhost";
$username = "root"; // default XAMPP username
$password = ""; // default XAMPP password (empty)
$dbname = "mydb"; // name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>

