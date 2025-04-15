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

// Ensure lecturer_messages table has necessary columns
$alter_table = "ALTER TABLE lecturer_messages 
                ADD COLUMN IF NOT EXISTS refuse_reason TEXT,
                ADD COLUMN IF NOT EXISTS status VARCHAR(20)";
if (!$conn->query($alter_table)) {
    die("Error altering table: " . $conn->error);
}

// Create notifications table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_matric VARCHAR(50),
    message TEXT,
    status VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($create_table)) {
    die("Error creating notifications table: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message_id = intval($_POST['message_id']);
    $student_matric = $_POST['student_matric'];
    $action = $_POST['action'];

    // First, mark all previous reschedule requests as handled
    $update_old = "UPDATE lecturer_messages 
                   SET status = 'handled' 
                   WHERE student_matric = ? 
                   AND type = 'reschedule' 
                   AND id != ?";
    $old_stmt = $conn->prepare($update_old);
    $old_stmt->bind_param("si", $student_matric, $message_id);
    $old_stmt->execute();

    if ($action == 'accept') {
        // Accept the current reschedule request
        $update_message = "UPDATE lecturer_messages 
                          SET status = 'accepted' 
                          WHERE id = ?";
        $stmt = $conn->prepare($update_message);
        $stmt->bind_param("i", $message_id);

        if ($stmt->execute()) {
            // Add a notification
            $notification_sql = "INSERT INTO notifications (student_matric, message, status) 
                               VALUES (?, 'Your rescheduling request has been accepted!', 'accepted')";
            $notify_stmt = $conn->prepare($notification_sql);
            $notify_stmt->bind_param("s", $student_matric);
            $notify_stmt->execute();
        }
    } elseif ($action == 'refuse') {
        $refuse_reason = $_POST['refuse_reason'];
        
        // Refuse the current reschedule request
        $update_message = "UPDATE lecturer_messages 
                          SET status = 'refused', 
                              refuse_reason = ? 
                          WHERE id = ?";
        $stmt = $conn->prepare($update_message);
        $stmt->bind_param("si", $refuse_reason, $message_id);

        if ($stmt->execute()) {
            // Add a notification
            $notification_sql = "INSERT INTO notifications (student_matric, message, status) 
                               VALUES (?, CONCAT('Your rescheduling request has been refused. Reason: ', ?), 'refused')";
            $notify_stmt = $conn->prepare($notification_sql);
            $notify_stmt->bind_param("ss", $student_matric, $refuse_reason);
            $notify_stmt->execute();
        }
    }
}

$conn->close();

// Redirect to dashboard after actions
header('Location: ../lecturer_dashboard.php');
exit();
?>