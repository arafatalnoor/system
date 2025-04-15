<?php
session_start();

// Check if user is logged in
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

// Mark notifications as read when viewed
$student_matric = $_SESSION['matric'];
$mark_read_sql = "UPDATE notifications SET is_read = 1 WHERE student_matric = ? AND is_read = 0";
$mark_read_stmt = $conn->prepare($mark_read_sql);
$mark_read_stmt->bind_param("s", $student_matric);
$mark_read_stmt->execute();
$mark_read_stmt->close();

// Create notifications table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_matric VARCHAR(50),
    message TEXT,
    status VARCHAR(20),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_table)) {
    die("Error creating notifications table: " . $conn->error);
}

// Get notifications for the student
$student_matric = $_SESSION['matric'];
$notifications_sql = "SELECT * FROM notifications WHERE student_matric = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($notifications_sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("s", $student_matric);
$stmt->execute();
$notifications = $stmt->get_result();

// Count unread notifications
$unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE student_matric = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("s", $student_matric);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];
$unread_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="Css/messages1.css">
</head>
<body>
    <header>
        <h1>Messages</h1>
    </header>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Dashboard</h2>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li>
                        <a href="messages.php" class="nav-link">
                            Messages
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container">
                <h2>Notifications</h2>
                <div class="notifications-list">
                    <?php if ($notifications->num_rows == 0): ?>
                        <p>No notifications yet.</p>
                    <?php else: ?>
                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <div class="notification <?php echo htmlspecialchars($notification['status']); ?>">
                                <div class="notification-content">
                                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small>
                                        <?php echo date('F j, Y g:i a', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
