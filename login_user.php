<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'mydb';

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve form data
$matric = $_POST['matric'] ?? null;
$role = $_POST['role'] ?? null;

// Validate required fields
if (!$matric || !$role) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body {
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                font-family: 'Arial', sans-serif;
                background: #f6f8fd;
            }
            .message-box {
                background: white;
                padding: 30px 40px;
                border-radius: 15px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 400px;
                width: 90%;
                position: relative;
                border: 1px solid rgba(255, 0, 0, 0.1);
            }
            .message-box p {
                color: #ff3333;
                font-size: 1.1rem;
                margin: 15px 0;
                line-height: 1.5;
            }
            .message-box::before {
                content: '⚠️';
                font-size: 2rem;
                display: block;
                margin-bottom: 15px;
            }
            .loading {
                width: 100%;
                height: 3px;
                background: #f0f0f0;
                position: absolute;
                bottom: 0;
                left: 0;
                border-radius: 0 0 15px 15px;
                overflow: hidden;
            }
            .loading::after {
                content: '';
                position: absolute;
                left: 0;
                height: 100%;
                width: 100%;
                background: #ff3333;
                animation: loading 2s linear forwards;
            }
            @keyframes loading {
                from { width: 100%; }
                to { width: 0%; }
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <p>Both Matric and Role are required.</p>
            <div class="loading"></div>
        </div>
        <script>
            setTimeout(function() {
                window.location.href = 'index.html';
            }, 2000);
        </script>
    </body>
    </html>
    <?php
    exit();
}

// Check if the user exists with the given role
$sql = "SELECT * FROM users WHERE matric = ? AND role = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $matric, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Store user data in session
    $_SESSION['matric'] = $matric;
    $_SESSION['role'] = $role;
    $_SESSION['name'] = $user['name'];
    $_SESSION['picture'] = $user['picture'] ?? 'default-profile.png';
    $_SESSION['logged_in'] = true;

    // Close database connections before redirect
    $stmt->close();
    $conn->close();

    if ($role == 'student') {
        header("Location: dashboard.php");
    } else if ($role == 'lecturer') {
        header("Location: lecturer_dashboard.php");
    }
    exit();
} else {
    $stmt->close();
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body {
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                font-family: 'Arial', sans-serif;
                background: #f6f8fd;
            }
            .message-box {
                background: white;
                padding: 30px 40px;
                border-radius: 15px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 400px;
                width: 90%;
                position: relative;
                border: 1px solid rgba(255, 0, 0, 0.1);
            }
            .message-box p {
                color: #ff3333;
                font-size: 1.1rem;
                margin: 15px 0;
                line-height: 1.5;
            }
            .message-box::before {
                content: '⚠️';
                font-size: 2rem;
                display: block;
                margin-bottom: 15px;
            }
            .loading {
                width: 100%;
                height: 3px;
                background: #f0f0f0;
                position: absolute;
                bottom: 0;
                left: 0;
                border-radius: 0 0 15px 15px;
                overflow: hidden;
            }
            .loading::after {
                content: '';
                position: absolute;
                left: 0;
                height: 100%;
                width: 100%;
                background: #ff3333;
                animation: loading 2s linear forwards;
            }
            @keyframes loading {
                from { width: 100%; }
                to { width: 0%; }
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <p>Invalid Matric or Role. Please try again or register first.</p>
            <div class="loading"></div>
        </div>
        <script>
            setTimeout(function() {
                window.location.href = 'index.html';
            }, 2000);
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>
