<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'mydb';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_matric = $_SESSION['matric'];
    $student_name = $_SESSION['name'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];

    $sql = "INSERT INTO appointments (student_matric, student_name, date, time, reason) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $student_matric, $student_name, $date, $time, $reason);
    
    if ($stmt->execute()) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Success</title>
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
                    border: 1px solid rgba(52, 152, 219, 0.1);
                }
                .message-box p {
                    color: #27ae60;
                    font-size: 1.1rem;
                    margin: 15px 0;
                    line-height: 1.5;
                }
                .message-box::before {
                    content: '✓';
                    font-size: 2rem;
                    display: block;
                    margin-bottom: 15px;
                    color: #27ae60;
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
                    background: #27ae60;
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
                <p>Appointment request submitted successfully!</p>
                <div class="loading"></div>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '../dashboard.php';
                }, 2000);
            </script>
        </body>
        </html>
        <?php
        exit();
    } else {
        echo "<script>
            alert('Error submitting appointment request.');
            window.location.href = '../Appointment.php';
        </script>";
    }

    $stmt->close();
}

$conn->close();
?> 