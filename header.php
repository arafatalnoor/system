<?php
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Use this in your navigation
if (isLoggedIn()) {
    echo '<a href="profile.php">Profile</a>';
} else {
    echo '<a href="login.php">Login</a>';
}
