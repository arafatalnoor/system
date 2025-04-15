<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['matric'])) {
    header("Location: login.php");
    exit();
}

// Database connection to get the latest picture
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'mydb';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// After database connection and before the availability query, add these lines
$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$timeSlots = ["08:00-10:00", "10:00-12:00", "12:00-14:00", "14:00-16:00", "16:00-18:00"];

// Get lecturer availability
$availability_sql = "SELECT DISTINCT day, time_slot FROM lecturer_availability 
    WHERE is_available = 1 
    ORDER BY 
        CASE day 
            WHEN 'Monday' THEN 1
            WHEN 'Tuesday' THEN 2
            WHEN 'Wednesday' THEN 3
            WHEN 'Thursday' THEN 4
            WHEN 'Friday' THEN 5
            WHEN 'Saturday' THEN 6
            WHEN 'Sunday' THEN 7
        END, 
        CASE time_slot
            WHEN '08:00-10:00' THEN 1
            WHEN '10:00-12:00' THEN 2
            WHEN '12:00-14:00' THEN 3
            WHEN '14:00-16:00' THEN 4
            WHEN '16:00-18:00' THEN 5
        END";
$availability_result = $conn->query($availability_sql);

if ($availability_result === false) {
    error_log("Error in availability query: " . $conn->error);
    $lecturer_schedule = [];
} else {
    $lecturer_schedule = [];
    while ($row = $availability_result->fetch_assoc()) {
        $lecturer_schedule[$row['day']][] = $row['time_slot'];
    }
}

// Add this debugging code after your database query
echo "<!-- Debug Information -->";
echo "<!-- Available Slots: -->";
echo "<!-- ";
print_r($lecturer_schedule);
echo " -->";

// Count unread notifications
$unread_sql = "SELECT COUNT(*) as unread_count FROM notifications 
               WHERE student_matric = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("s", $matric);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];
$unread_stmt->close();

// Get the latest user data
$matric = $_SESSION['matric'];
$sql = "SELECT name, picture FROM users WHERE matric = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matric);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];
    $profileImage = !empty($user['picture']) ? $user['picture'] : 'default-profile.png';
    
    // Update session with latest data
    $_SESSION['name'] = $name;
    $_SESSION['picture'] = $profileImage;
} else {
    $name = $_SESSION['name'] ?? 'Guest';
    $profileImage = $_SESSION['picture'] ?? 'default-profile.png';
}

// Get notification counts by type
$sql_counts = "SELECT 
    SUM(CASE WHEN status = 'accepted' AND is_read = 0 THEN 1 ELSE 0 END) as accepted_count,
    SUM(CASE WHEN status = 'refused' AND is_read = 0 THEN 1 ELSE 0 END) as refused_count
    FROM notifications 
    WHERE student_matric = ?";
$count_stmt = $conn->prepare($sql_counts);
$count_stmt->bind_param("s", $matric);
$count_stmt->execute();
$counts = $count_stmt->get_result()->fetch_assoc();
$count_stmt->close();

$accepted_count = $counts['accepted_count'];
$refused_count = $counts['refused_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="Css/dashboard.css">
</head>
<body>
    <header>
        <h1>Dashboard</h1>
        <div class="current-time">
            <span id="current-time"></span>
        </div>
    </header>
    <div class="dashboard-container">
        <!-- Available Slots Section - Move to left sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Dashboard</h2>
            </div>
            
            <!-- Navigation Links -->
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li>
                        <a href="messages.php" class="nav-link">
                            Messages
                            <?php if ($accepted_count > 0): ?>
                                <span class="notification-badge accepted"><?php echo $accepted_count; ?></span>
                            <?php endif; ?>
                            <?php if ($refused_count > 0): ?>
                                <span class="notification-badge refused"><?php echo $refused_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Section -->
        <main class="main-content">
            <section class="headerbar">
                <div class="user-info">
                    <!-- Display profile image -->
                    <img id="profileImage" src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="profile-image">
                    <span id="user-name"><?php echo htmlspecialchars($name); ?></span>
                </div>
                
                <!-- Available Slots Preview -->
                <div class="available-slots-preview">
                    <div class="slots-preview-header">
                        <span class="time-icon">🕒</span>
                        <span>Available Slots</span>
                    </div>
                    <div class="slots-preview-content">
                        <?php
                        $currentDay = date('l');
                        $currentTime = date('H:i');
                        $slotsFound = false;

                        foreach ($days as $day) {
                            if (isset($lecturer_schedule[$day]) && !empty($lecturer_schedule[$day])) {
                                foreach ($lecturer_schedule[$day] as $slot) {
                                    // Add debug output
                                    error_log("Processing slot: Day=$day, Time=$slot");
                                    
                                    $slotsFound = true;
                                    ?>
                                    <div class="preview-slot">
                                        <span class="preview-day"><?php echo htmlspecialchars($day); ?></span>
                                        <span class="preview-time"><?php echo htmlspecialchars($slot); ?></span>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        
                        if (!$slotsFound) {
                            echo '<div class="no-slots">No available slots</div>';
                            // Add debug output
                            error_log("No slots found in lecturer_schedule: " . print_r($lecturer_schedule, true));
                        }
                        ?>
                    </div>
                </div>
            </section>

            <section class="container">
                <div class="card1">
                    <h3>Welcome back, <?php echo htmlspecialchars($name); ?>!</h3>
                    <p>Here's a quick overview of your account and activity.</p>
                </div>

                <a href="appointment.php" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <h3>Appointment</h3>
                    </div>
                </a>

                <a href="Reschedule.php" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <h3>Reschedule</h3>
                    </div>
                </a>

                <a href="Cancel.php" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <h3>Cancel</h3>
                    </div>
                </a>
            </section>
        </main>
    </div>
    <!-- Add this before closing body tag -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update current time function
        function updateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            
            const dateTimeString = now.toLocaleDateString('en-US', options);
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            // Update the time display
            document.getElementById('current-time').textContent = dateTimeString;
            
            // Update header time if it exists
            const headerTime = document.getElementById('header-time');
            if (headerTime) {
                headerTime.textContent = timeString;
            }
        }

        // Initial update
        updateTime();

        // Update every second
        setInterval(updateTime, 1000);

        // Existing tooltip code
        const timeCells = document.querySelectorAll('.time-cell');
        timeCells.forEach(cell => {
            if (cell.classList.contains('available')) {
                const day = cell.dataset.day;
                const time = cell.dataset.time;
                cell.setAttribute('data-tooltip', `Available on ${day} at ${time}`);
                
                cell.addEventListener('click', function() {
                    if(!cell.classList.contains('past')) {
                        cell.classList.add('highlight');
                        setTimeout(() => {
                            cell.classList.remove('highlight');
                        }, 1000);
                    }
                });
            }
        });

        // Add smooth scroll to timetable
        const timetable = document.querySelector('.timetable');
        let isDown = false;
        let startX;
        let scrollLeft;

        timetable.addEventListener('mousedown', (e) => {
            isDown = true;
            timetable.style.cursor = 'grabbing';
            startX = e.pageX - timetable.offsetLeft;
            scrollLeft = timetable.scrollLeft;
        });

        timetable.addEventListener('mouseleave', () => {
            isDown = false;
            timetable.style.cursor = 'grab';
        });

        timetable.addEventListener('mouseup', () => {
            isDown = false;
            timetable.style.cursor = 'grab';
        });

        timetable.addEventListener('mousemove', (e) => {
            if(!isDown) return;
            e.preventDefault();
            const x = e.pageX - timetable.offsetLeft;
            const walk = (x - startX) * 2;
            timetable.scrollLeft = scrollLeft - walk;
        });

        // Add hover effect to legend items
        const legendItems = document.querySelectorAll('.legend-item');
        legendItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                const status = this.textContent.trim().toLowerCase();
                const cells = document.querySelectorAll(`.time-cell.${status}`);
                cells.forEach(cell => cell.classList.add('highlight'));
            });

            item.addEventListener('mouseleave', function() {
                const cells = document.querySelectorAll('.time-cell');
                cells.forEach(cell => cell.classList.remove('highlight'));
            });
        });
    });
    </script>
</body>
</html>
