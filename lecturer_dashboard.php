<?php
// Start session
session_start();

// Database connection - Must be at the top
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'mydb';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create lecturer_availability table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS lecturer_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day VARCHAR(20) NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_table)) {
    die("Error creating table: " . $conn->error);
}

// Fetch current availability when page loads
$fetch_sql = "SELECT day, time_slot FROM lecturer_availability WHERE is_available = 1";
$result = $conn->query($fetch_sql);

// Initialize current_availability array
$current_availability = [];

// Check if query was successful
if ($result === false) {
    error_log("Error in availability query: " . $conn->error);
} else {
    while ($row = $result->fetch_assoc()) {
        $current_availability[$row['day']][] = $row['time_slot'];
    }
}

if (isset($_SESSION['name'])) {
    $name = $_SESSION['name'];
} else {
    $name = "Lecturer"; // Default value if name is not set
}

// Check if there's a message set in the session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
} else {
    $message = ""; // Default empty string if no message is set
}

// Handle availability submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['availability'])) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Clear previous availability
        $clear_sql = "DELETE FROM lecturer_availability";
        $conn->query($clear_sql);
        
        $availability = $_POST['availability'];
        
        $insert_sql = "INSERT INTO lecturer_availability (day, time_slot, is_available) VALUES (?, ?, 1)";
        $stmt = $conn->prepare($insert_sql);
        
        foreach ($availability as $day => $slots) {
            if (is_array($slots)) {
                foreach ($slots as $slot) {
                    $stmt->bind_param("ss", $day, $slot);
                    if (!$stmt->execute()) {
                        throw new Exception("Error executing statement: " . $stmt->error);
                    }
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set a success message
        $_SESSION['message'] = "Availability saved successfully!";
        
        // Redirect to avoid form resubmission
        header("Location: lecturer_dashboard.php");
        exit();
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['message'] = "Error saving availability: " . $e->getMessage();
    }
}

// Add these count queries
// Count pending appointments
$count_sql = "SELECT COUNT(*) as pending_count FROM appointments WHERE status = 'pending'";
$count_result = $conn->query($count_sql);
$pending_count = 0;

if ($count_result && $count_result->num_rows > 0) {
    $row = $count_result->fetch_assoc();
    $pending_count = $row['pending_count'];
}

// Count rescheduled appointments
$reschedule_sql = "SELECT COUNT(*) as reschedule_count FROM lecturer_messages 
    WHERE type = 'reschedule' 
    AND (status IS NULL OR status = 'pending')";
$reschedule_result = $conn->query($reschedule_sql);
$reschedule_count = 0;

if ($reschedule_result && $reschedule_result->num_rows > 0) {
    $row = $reschedule_result->fetch_assoc();
    $reschedule_count = $row['reschedule_count'];
}

// Count cancelled appointments
$cancel_sql = "SELECT COUNT(*) as cancel_count FROM lecturer_messages WHERE type = 'cancel' AND (status IS NULL OR status != 'handled')";
$cancel_result = $conn->query($cancel_sql);
$cancel_count = 0;

if ($cancel_result && $cancel_result->num_rows > 0) {
    $row = $cancel_result->fetch_assoc();
    $cancel_count = $row['cancel_count'];
}

// First, check if the appointments table exists
$check_table = "SHOW TABLES LIKE 'appointments'";
$table_exists = $conn->query($check_table);

if ($table_exists->num_rows == 0) {
    // Create the appointments table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_matric VARCHAR(50),
        student_name VARCHAR(100),
        date DATE,
        time TIME,
        reason TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        refuse_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($create_table)) {
        die("Error creating table: " . $conn->error);
    }
}

// After the first CREATE TABLE, add this:
$create_reschedule_table = "CREATE TABLE IF NOT EXISTS rescheduled_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_matric VARCHAR(50),
    student_name VARCHAR(100),
    original_date DATE,
    original_time TIME,
    new_date DATE,
    new_time TIME,
    reason TEXT,
    status ENUM('rescheduled') DEFAULT 'rescheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_reschedule_table)) {
    die("Error creating rescheduled appointments table: " . $conn->error);
}

// Add this after your other CREATE TABLE queries
$create_notifications_table = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_matric VARCHAR(50),
    message TEXT,
    status VARCHAR(20),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_notifications_table)) {
    die("Error creating notifications table: " . $conn->error);
}

// Add this after your other CREATE TABLE queries
$create_lecturer_messages = "CREATE TABLE IF NOT EXISTS lecturer_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_matric VARCHAR(50),
    student_name VARCHAR(100),
    message TEXT,
    type VARCHAR(20),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_lecturer_messages)) {
    die("Error creating lecturer messages table: " . $conn->error);
}

// Add status column to lecturer_messages table if it doesn't exist
$alter_table = "ALTER TABLE lecturer_messages 
                ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT NULL";
if (!$conn->query($alter_table)) {
    die("Error altering lecturer_messages table: " . $conn->error);
}

// Fetch pending appointments using prepared statement
$appointments = [];
try {
    $sql = "SELECT * FROM appointments WHERE status = 'pending' ORDER BY date, time";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        $stmt->close();
    } else {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Handle appointment responses
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accept_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        
        // Get appointment details
        $get_appt = "SELECT student_matric, date, time FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($get_appt);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $appt = $stmt->get_result()->fetch_assoc();
        
        // Update appointment status
        $sql = "UPDATE appointments SET status = 'accepted' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute()) {
            // Create notification
            $message = "Your appointment for " . $appt['date'] . " at " . $appt['time'] . " has been accepted.";
            $insert_notif = "INSERT INTO notifications (student_matric, message, status) VALUES (?, ?, 'accepted')";
            $notif_stmt = $conn->prepare($insert_notif);
            $notif_stmt->bind_param("ss", $appt['student_matric'], $message);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        $stmt->close();
        header("Location: lecturer_dashboard.php");
        exit();
    }
    
    if (isset($_POST['refuse_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $refuse_reason = $_POST['refuse_reason'];
        
        // Get appointment details
        $get_appt = "SELECT student_matric, date, time FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($get_appt);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $appt = $stmt->get_result()->fetch_assoc();
        
        // Update appointment status
        $sql = "UPDATE appointments SET status = 'refused', refuse_reason = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $refuse_reason, $appointment_id);
        
        if ($stmt->execute()) {
            // Create notification
            $message = "Your appointment for " . $appt['date'] . " at " . $appt['time'] . " has been refused. Reason: " . $refuse_reason;
            $insert_notif = "INSERT INTO notifications (student_matric, message, status) VALUES (?, ?, 'refused')";
            $notif_stmt = $conn->prepare($insert_notif);
            $notif_stmt->bind_param("ss", $appt['student_matric'], $message);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        $stmt->close();
        header("Location: lecturer_dashboard.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard</title>
    <link rel="stylesheet" href="Css/lecturer_dashboard1.css">
</head>

<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <h1>Welcome, Dr. <?php echo htmlspecialchars($name); ?>!</h1>
            <nav>
                <ul>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Dashboard -->
    <main class="container">
        <section id="dashboard">
            <h2>Dashboard Overview</h2>
            <div class="overview">
                <div class="stat">
                    <h3>New Appointment Requests</h3>
                    <p data-count="<?php echo htmlspecialchars($pending_count); ?>">
                        <?php echo htmlspecialchars($pending_count); ?>
                    </p>
                </div>
                <div class="stat">
                    <h3>Rescheduled Appointments</h3>
                    <p data-count="<?php echo htmlspecialchars($reschedule_count); ?>">
                        <?php echo htmlspecialchars($reschedule_count); ?>
                    </p>
                </div>
                <div class="stat">
                    <h3>Cancelled Appointments</h3>
                    <p data-count="<?php echo htmlspecialchars($cancel_count); ?>">
                        <?php echo htmlspecialchars($cancel_count); ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- Time Table Section -->
        <section id="timetable">
            <h2>Set Availability</h2>
            <form id="availability-form" method="POST" action="">
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>08:00 - 10:00</th>
                            <th>10:00 - 12:00</th>
                            <th>12:00 - 14:00</th>
                            <th>14:00 - 16:00</th>
                            <th>16:00 - 18:00</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                        $timeSlots = ["08:00-10:00", "10:00-12:00", "12:00-14:00", "14:00-16:00", "16:00-18:00"];
                        foreach ($days as $day) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($day) . "</td>";
                            foreach ($timeSlots as $slot) {
                                $checked = isset($current_availability[$day]) && 
                                          in_array($slot, $current_availability[$day]) ? 'checked' : '';
                                echo "<td><input type='checkbox' name='availability[$day][]' value='$slot' $checked></td>";
                            }
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <button type="submit">Save Availability</button>
            </form>
            <?php if (!empty($saveStatus)) echo "<p>" . htmlspecialchars($saveStatus) . "</p>"; ?>
        </section>

        <!-- Messages Section -->
        <section id="messages">
            <h2>Appointment Requests</h2>
            <div class="message-list">
                <?php
                // Fetch appointments that can be acted upon (pending or already decided)
                $appointments_sql = "SELECT * FROM appointments 
                                   WHERE status IN ('pending', 'accepted', 'refused') 
                                   ORDER BY created_at DESC";
                $appointments_result = $conn->query($appointments_sql);
                
                if ($appointments_result->num_rows == 0): 
                ?>
                    <p>No appointment requests.</p>
                <?php else: 
                    while($appointment = $appointments_result->fetch_assoc()): 
                ?>
                    <div class="message <?php echo $appointment['status']; ?>">
                        <div class="appointment-details">
                            <strong><?php echo htmlspecialchars($appointment['student_name']); ?></strong>
                            <p>Date: <?php echo htmlspecialchars($appointment['date']); ?></p>
                            <p>Time: <?php echo htmlspecialchars($appointment['time']); ?></p>
                            <p>Reason: <?php echo htmlspecialchars($appointment['reason']); ?></p>
                            <?php if($appointment['status'] == 'refused'): ?>
                                <p class="refuse-reason">Refused reason: <?php echo htmlspecialchars($appointment['refuse_reason']); ?></p>
                            <?php endif; ?>
                            <p class="status">Current status: <span class="status-badge <?php echo $appointment['status']; ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span></p>
                        </div>
                        <div class="response-options">
                            <form method="POST" action="PhP/handle_appointment.php" style="display: inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                <?php if($appointment['status'] != 'accepted'): ?>
                                    <button type="submit" name="action" value="accept" class="accept">Accept</button>
                                <?php endif; ?>
                                <?php if($appointment['status'] != 'refused'): ?>
                                    <button type="button" class="refuse" onclick="toggleReasonInput(this)">Refuse</button>
                                    <div class="refuse-reason" style="display: none;">
                                        <textarea name="refuse_reason" rows="4" cols="50"  
                                            placeholder="Please provide a reason for refusal"></textarea>
                                        <button type="submit" name="action" value="refuse" class="send-reason">Send</button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php 
                    endwhile;
                endif; 
                ?>
            </div>
        </section>

        <!-- Rescheduled Appointments Section -->
        <section id="messages2">
    <h2>Rescheduled Appointments</h2>
    <div class="message-list">
        <?php
        // Fetch reschedule messages with appointment details (deduplicated query)
        $messages_sql = "
    SELECT lm.*, ra.original_date, ra.original_time, ra.new_date, ra.new_time 
    FROM lecturer_messages lm
    LEFT JOIN rescheduled_appointments ra ON lm.student_matric = ra.student_matric 
        AND lm.created_at = ra.created_at
    WHERE lm.type = 'reschedule'
    ORDER BY lm.created_at DESC";






    
        $messages_result = $conn->query($messages_sql);
        
        if ($messages_result->num_rows == 0): 
        ?>
            <p>No rescheduled appointments.</p>
        <?php else: 
            while ($message = $messages_result->fetch_assoc()): 
        ?>
            <div class="message <?php echo $message['status'] ?? 'pending'; ?>">
                <div class="appointment-details">
                    <strong><?php echo htmlspecialchars($message['student_name']); ?></strong>
                    <div class="message-content">
                        <p><?php echo htmlspecialchars($message['message']); ?></p>
                        <?php if ($message['original_date'] && $message['original_time']): ?>
                            <p>Original Appointment: <?php echo htmlspecialchars($message['original_date']); ?> at <?php echo htmlspecialchars($message['original_time']); ?></p>
                            <p>New Appointment: <?php echo htmlspecialchars($message['new_date']); ?> at <?php echo htmlspecialchars($message['new_time']); ?></p>
                        <?php endif; ?>
                        <small>Requested on: <?php echo date('F j, Y g:i a', strtotime($message['created_at'])); ?></small>
                    </div>
                    <?php if ($message['status'] == 'refused'): ?>
                        <p class="refuse-reason">Refused reason: <?php echo htmlspecialchars($message['refuse_reason']); ?></p>
                    <?php endif; ?>
                    <?php if ($message['status']): ?>
                        <p class="status">Current status: 
                            <span class="status-badge <?php echo $message['status']; ?>">
                                <?php echo ucfirst($message['status']); ?>
                            </span>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="response-options">
                    <!-- Separate form for Accept -->
                    <form method="POST" action="PhP/handle_reschedule.php" style="display: inline-block;">
                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                        <input type="hidden" name="student_matric" value="<?php echo $message['student_matric']; ?>">
                        <?php if ($message['status'] != 'accepted'): ?>
                            <button type="submit" name="action" value="accept" class="accept">Accept</button>
                        <?php endif; ?>
                    </form>
                    
                    <!-- Separate form for Refuse -->
                    <form method="POST" action="PhP/handle_reschedule.php" style="display: inline-block;">
                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                        <input type="hidden" name="student_matric" value="<?php echo $message['student_matric']; ?>">
                        <?php if ($message['status'] != 'refused'): ?>
                            <button type="button" class="refuse" onclick="toggleReasonInput(this)">Refuse</button>
                            <div class="refuse-reason-input" style="display: none;">
                                <textarea name="refuse_reason" rows="4" cols="50"  
                                    placeholder="Please provide a reason for refusal"></textarea>
                                <button type="submit" name="action" value="refuse" class="send-reason">Send</button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php 
            endwhile;
        endif; 
        ?>
    </div>
</section>


        <!-- Cancelled Appointments Section -->
        <section id="messages">
            <h2>Cancellation</h2>
            <div class="message-list">
                <?php
                // Fetch cancellation messages
                $cancel_sql = "SELECT * FROM lecturer_messages 
                               WHERE type = 'cancel' 
                               AND (is_read = 0 OR is_read IS NULL)
                               AND (status IS NULL OR status != 'handled')
                               ORDER BY created_at DESC";
                $cancel_stmt = $conn->prepare($cancel_sql);
                $cancel_stmt->execute();
                $cancel_result = $cancel_stmt->get_result();
                ?>

                <?php if ($cancel_result->num_rows == 0): ?>
                    <p>No cancellation requests.</p>
                <?php else: ?>
                    <?php while ($cancel = $cancel_result->fetch_assoc()): ?>
                        <div class="message cancelled">
                            <div class="appointment-details">
                                <strong><?php echo htmlspecialchars($cancel['student_name']); ?></strong>
                                <div class="message-content">
                                    <p><?php echo htmlspecialchars($cancel['message']); ?></p>
                                    <small>Requested on: <?php echo date('F j, Y g:i a', strtotime($cancel['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                <?php $cancel_stmt->close(); ?>
            </div>
        </section>
    </main>
    <script src="Js/lecturer_dashboard.js"></script>
    <script>
    function toggleReasonInput(button) {
        const reasonDiv = button.nextElementSibling;
        reasonDiv.style.display = reasonDiv.style.display === 'none' ? 'block' : 'none';
    }
    </script>
</body>

</html>
<?php
// Only close the connection once, at the very end
$conn->close();
?>
