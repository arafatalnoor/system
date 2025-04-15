<?php
session_start();
if (!isset($_SESSION['matric'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment</title>
    <link rel="stylesheet" href="Css/nav.css">
    <link rel="stylesheet" href="Css/appointment.css">
</head>

<body>
    <?php include 'components/nav.php'; ?>
    
    <div class="container">
        <form action="PhP/save_appointment.php" method="post" id="appointmentForm" class="appointment-form">
            <h1 class="appointment-title">Book an Appointment</h1>
            
            <div class="form-group">
                <label for="date">Preferred Date</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="form-group">
                <label for="time">Preferred Time</label>
                <input type="time" id="time" name="time" required>
            </div>

            <div class="form-group">
                <label for="reason">Reason for Appointment</label>
                <textarea id="reason" name="reason" rows="5" 
                    placeholder="Please provide a brief reason for your appointment" required></textarea>
            </div>

            <button type="submit">Book Appointment</button>
        </form>
    </div>
</body>

</html>
