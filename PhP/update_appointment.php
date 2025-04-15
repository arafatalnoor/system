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

// Create rescheduled_appointments table if it doesn't exist
$create_reschedule_table = "CREATE TABLE IF NOT EXISTS rescheduled_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_matric VARCHAR(50),
    student_name VARCHAR(100),
    original_date DATE,
    original_time TIME,
    new_date DATE,
    new_time TIME,
    reason TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_reschedule_table)) {
    die("Error creating rescheduled appointments table: " . $conn->error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_matric = $_SESSION['matric'];
    $student_name = $_SESSION['name'];
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    $reason = $_POST['reason'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // First check if there's an appointment to reschedule
        $check_sql = "SELECT * FROM appointments 
                      WHERE student_matric = ? 
                      AND (status = 'pending' OR status = 'accepted')
                      ORDER BY created_at DESC LIMIT 1";
        
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $student_matric);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows == 0) {
            throw new Exception('No active appointment found to reschedule.');
        }

        $original = $result->fetch_assoc();
        
        // Insert into rescheduled_appointments
        $insert_reschedule = "INSERT INTO rescheduled_appointments 
            (student_matric, student_name, original_date, original_time, new_date, new_time, reason) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $reschedule_stmt = $conn->prepare($insert_reschedule);
        $reschedule_stmt->bind_param("sssssss", 
            $student_matric, 
            $student_name, 
            $original['date'],
            $original['time'],
            $new_date,
            $new_time,
            $reason
        );
        $reschedule_stmt->execute();
        
        // Update the original appointment status
        $update_sql = "UPDATE appointments 
                      SET status = 'rescheduled'
                      WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $original['id']);
        $update_stmt->execute();
        
        // Add message to lecturer's dashboard
        $message = "Request to reschedule appointment from {$original['date']} {$original['time']} to {$new_date} {$new_time}. Reason: {$reason}";
        
        $insert_msg = "INSERT INTO lecturer_messages (student_matric, student_name, message, type) 
                      VALUES (?, ?, ?, 'reschedule')";
        $msg_stmt = $conn->prepare($insert_msg);
        $msg_stmt->bind_param("sss", $student_matric, $student_name, $message);
        $msg_stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Show success message
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
                <p>Appointment rescheduling request submitted successfully!</p>
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

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.location.href = '../" . ($e->getMessage() == 'No active appointment found to reschedule.' ? 'Appointment' : 'Reschedule') . ".php';
        </script>";
    }
}


$conn->close();
?>