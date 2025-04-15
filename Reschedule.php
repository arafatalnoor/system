<?php
session_start();
if (!isset($_SESSION['matric'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'mydb';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for active appointment
$student_matric = $_SESSION['matric'];
$check_sql = "SELECT * FROM appointments 
              WHERE student_matric = ? 
              AND status = 'pending'
              ORDER BY created_at DESC LIMIT 1";

$stmt = $conn->prepare($check_sql);
$stmt->bind_param("s", $student_matric);
$stmt->execute();
$result = $stmt->get_result();
$active_appointment = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment</title>
    <link rel="stylesheet" href="Css/nav.css">
    <link rel="stylesheet" href="Css/appointment.css">
</head>
<body>
    <?php include 'components/nav.php'; ?>
    
    <div class="container">
        <?php if ($active_appointment): ?>
            <form action="PhP/update_appointment.php" method="post" id="rescheduleForm" class="appointment-form" onsubmit="return validateForm()">
                <h1 class="appointment-title">Reschedule Appointment</h1>

                <!-- Current appointment details -->
                <div class="current-appointment">
                    <h3>Current Appointment Details</h3>
                    <p>Date: <?php echo htmlspecialchars($active_appointment['date']); ?></p>
                    <p>Time: <?php echo htmlspecialchars($active_appointment['time']); ?></p>
                    <p>Status: <?php echo ucfirst(htmlspecialchars($active_appointment['status'])); ?></p>
                </div>

                <input type="hidden" name="student_matric" value="<?php echo $_SESSION['matric']; ?>">
                <input type="hidden" name="student_name" value="<?php echo $_SESSION['name']; ?>">

                <!-- New appointment details -->
                <div class="form-group">
                    <label for="new-date">New Date</label>
                    <input type="date" id="new-date" name="new_date" required min="<?php echo date('Y-m-d'); ?>">
                    <div class="date-warning" id="dateWarning">Please select a future date</div>
                </div>

                <div class="form-group">
                    <label for="new-time">New Time</label>
                    <input type="time" id="new-time" name="new_time" required>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Rescheduling</label>
                    <textarea 
                        id="reason" 
                        name="reason" 
                        rows="4" 
                        placeholder="Please provide a reason for rescheduling your appointment"
                        required
                    ></textarea>
                </div>

                <button type="submit" class="reschedule-button">Reschedule Appointment</button>
            </form>
        <?php else: ?>
            <div class="message-box">
                <h2>No Pending Appointment</h2>
                <p>You don't have any pending appointments to reschedule. If your appointment was accepted or refused by the lecturer, you'll need to make a new appointment.</p>
                <a href="Appointment.php" class="btn">Make New Appointment</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function validateForm() {
            const dateInput = document.getElementById('new-date');
            const selectedDate = new Date(dateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const dateWarning = document.getElementById('dateWarning');

            if (selectedDate < today) {
                dateWarning.style.display = 'block';
                return false;
            }

            dateWarning.style.display = 'none';
            return true;
        }

        // Set min date for date input
        document.getElementById('new-date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html> 