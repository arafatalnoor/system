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

if (isset($_POST['accept_reschedule'])) {
    $message_id = $_POST['message_id'];
    $student_matric = $_POST['student_matric'];
    
    // Create notification for student
    $notif_sql = "INSERT INTO notifications (student_matric, message, status) 
                  VALUES (?, 'Your rescheduling request has been accepted.', 'accepted')";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param("s", $student_matric);
    $notif_stmt->execute();
    $notif_stmt->close();
    
    // Mark the message as handled (delete or update status)
    $update_sql = "UPDATE lecturer_messages SET is_read = 1, status = 'handled' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $message_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    header("Location: lecturer_dashboard.php");
    exit();
}

if (isset($_POST['refuse_reschedule'])) {
    $message_id = $_POST['message_id'];
    $student_matric = $_POST['student_matric'];
    $refuse_reason = $_POST['refuse_reason'];
    
    // Create notification for student
    $message = "Your rescheduling request has been refused. Reason: " . $refuse_reason;
    $notif_sql = "INSERT INTO notifications (student_matric, message, status) 
                  VALUES (?, ?, 'refused')";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param("ss", $student_matric, $message);
    $notif_stmt->execute();
    $notif_stmt->close();
    
    // Mark the message as handled (delete or update status)
    $update_sql = "UPDATE lecturer_messages SET is_read = 1, status = 'handled' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $message_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    header("Location: lecturer_dashboard.php");
    exit();
}

$conn->close();
?>
