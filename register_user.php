<?php
// register_user.php

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
$name = $_POST['name'] ?? null;
$matric = $_POST['matric'] ?? null;
$role = $_POST['role'] ?? null;

// Validate required fields
if (!$name || !$matric || !$role) {
    echo "<p style='color:red;'>All fields are required.</p>";
    exit();
}

// Check if the user already exists
$sql = "SELECT * FROM users WHERE matric = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matric);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User already exists
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Registration Error</title>
        <style>
            body {
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                font-family: 'Arial', sans-serif;
                background: linear-gradient(135deg, #f6f8fd 0%, #f1f4f9 100%);
            }
            .message-box {
                background: white;
                padding: 35px 40px;
                border-radius: 20px;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
                text-align: center;
                max-width: 420px;
                width: 90%;
                position: relative;
                border: 1px solid rgba(30, 60, 114, 0.1);
                animation: fadeIn 0.5s ease-out;
            }
            .message-box p {
                color: #1e3c72;
                font-size: 1.1rem;
                margin: 15px 0;
                line-height: 1.6;
            }
            .message-box::before {
                content: 'ℹ️';
                font-size: 2.5rem;
                display: block;
                margin-bottom: 20px;
            }
            .loading {
                width: 100%;
                height: 4px;
                background: #f0f0f0;
                position: absolute;
                bottom: 0;
                left: 0;
                border-radius: 0 0 20px 20px;
                overflow: hidden;
            }
            .loading::after {
                content: '';
                position: absolute;
                left: 0;
                height: 100%;
                width: 100%;
                background: linear-gradient(90deg, #1e3c72, #2a5298);
                animation: loading 2s linear forwards;
            }
            @keyframes loading {
                from { width: 100%; }
                to { width: 0%; }
            }
            @keyframes fadeIn {
                from { 
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to { 
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .matric {
                color: #2a5298;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <p>User with Matric <span class="matric">'<?php echo htmlspecialchars($matric); ?>'</span> already exists.</p>
            <p>Please proceed to login.</p>
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
} else {
    // Insert new user
    $sql = "INSERT INTO users (name, matric, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $matric, $role);

    if ($stmt->execute()) {
        // Send success message and include JavaScript for delayed redirect
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Registration Successful</title>
            <style>
                .success-message {
                    text-align: center;
                    margin-top: 100px;
                    font-family: Arial, sans-serif;
                }
                .success-box {
                    color: green;
                    padding: 20px;
                    border: 1px solid green;
                    border-radius: 5px;
                    display: inline-block;
                }
            </style>
        </head>
        <body>
            <div class="success-message">
                <div class="success-box">
                    <h2>Registration Successful!</h2>
                    <p>You can now log in.</p>
                    <p>Redirecting to login page...</p>
                </div>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'index.html';
                }, 2000); // Redirect after 2 seconds
            </script>
        </body>
        </html>
        <?php
        exit();
    } else {
        echo "<p style='color:red;'>Error saving user data: " . $stmt->error . "</p>";
    }
}

// Close connection
$stmt->close();
$conn->close();
?>
