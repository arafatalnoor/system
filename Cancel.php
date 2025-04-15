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
    <title>Cancel Appointment</title>
    <link rel="stylesheet" href="Css/nav.css">
    <link rel="stylesheet" href="Css/appointment.css">
</head>
<body>
    <?php include 'components/nav.php'; ?>
    
    <div class="container">
        <form action="PhP/cancel_appointment.php" method="post" id="cancelForm" class="appointment-form">
            <h1 class="appointment-title">Cancel Appointment</h1>

            <!-- Add hidden fields for student info -->
            <input type="hidden" name="student_matric" value="<?php echo $_SESSION['matric']; ?>">
            <input type="hidden" name="student_name" value="<?php echo $_SESSION['name']; ?>">

            <div class="form-group">
                <label for="reason">Reason for Cancellation</label>
                <textarea 
                    id="reason" 
                    name="reason" 
                    rows="5" 
                    placeholder="Please provide a reason for cancelling your appointment"
                    required
                ></textarea>
            </div>

            <button type="submit" class="cancel-button">Cancel Appointment</button>
        </form>
    </div>
</body>
</html> 