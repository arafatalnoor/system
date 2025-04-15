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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_id = intval($_POST['appointment_id']); // Sanitize input
    $action = $_POST['action'];

    // Fetch appointment details
    $get_appointment = "SELECT student_matric FROM appointments WHERE id = ?";
    if ($stmt = $conn->prepare($get_appointment)) {
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$appointment) {
            die("Error: Appointment not found.");
        }
        
        $student_matric = $appointment['student_matric'];
    } else {
        die("SQL error: " . $conn->error);
    }

    if ($action == 'accept') {
        // Refuse other pending appointments
        $update_others = "UPDATE appointments 
                         SET status = 'refused', 
                             refuse_reason = 'Another appointment was accepted'
                         WHERE student_matric = ? 
                         AND id != ? 
                         AND status = 'pending'";
        if ($stmt = $conn->prepare($update_others)) {
            $stmt->bind_param("si", $student_matric, $appointment_id);
            $stmt->execute();
            $stmt->close();
        } else {
            die("SQL error: " . $conn->error);
        }

        // Accept the selected appointment
        $sql = "UPDATE appointments SET status = 'accepted' WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $appointment_id);
            if ($stmt->execute()) {
                // Update messages and add notification
                handleMessagesAndNotifications($conn, $student_matric, $appointment_id, 'accepted', null);
            }
            $stmt->close();
        } else {
            die("SQL error: " . $conn->error);
        }
    } elseif ($action == 'refuse') {
        $refuse_reason = $_POST['refuse_reason'];

        // Refuse the appointment
        $sql = "UPDATE appointments SET status = 'refused', refuse_reason = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $refuse_reason, $appointment_id);
            if ($stmt->execute()) {
                // Update messages and add notification
                handleMessagesAndNotifications($conn, $student_matric, $appointment_id, 'refused', $refuse_reason);
            }
            $stmt->close();
        } else {
            die("SQL error: " . $conn->error);
        }
    }
}

$conn->close();
header('Location: ../lecturer_dashboard.php');
exit();

/**
 * Handle messages and notifications
 */
function handleMessagesAndNotifications($conn, $student_matric, $appointment_id, $status, $reason)
{
    // Update related messages
    $update_messages = "UPDATE lecturer_messages 
                      SET status = 'handled' 
                      WHERE student_matric = ? 
                      AND (appointment_id = ? OR type IN ('reschedule', 'cancel'))";
    if ($stmt = $conn->prepare($update_messages)) {
        $stmt->bind_param("si", $student_matric, $appointment_id);
        $stmt->execute();
        $stmt->close();
    }

    // Add notification
    $message = $status === 'accepted' ? 
               'Your appointment has been accepted!' : 
               "Your appointment has been refused. Reason: $reason";
    $notification_sql = "INSERT INTO notifications (student_matric, message, status) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($notification_sql)) {
        $stmt->bind_param("sss", $student_matric, $message, $status);
        $stmt->execute();
        $stmt->close();
    }
}
?>
