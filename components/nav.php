<?php
function isCurrentPage($pageName) {
    // Get the current page filename without query parameters
    $currentPage = strtolower(basename($_SERVER['PHP_SELF']));
    $pageName = strtolower($pageName);
    // Debug line (optional)
    // error_log("Current: $currentPage, Checking: $pageName");
    return ($currentPage === $pageName) ? 'active' : '';
}
?>

<link rel="stylesheet" href="css/nav.css">

<nav class="nav">
    <div class="nav-brand">
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
    </div>
    <ul class="nav-links">
        <li><a href="Appointment.php" class="nav__link <?php echo isCurrentPage('Appointment.php'); ?>">Appointment</a></li>
        <li><a href="Reschedule.php" class="nav__link <?php echo isCurrentPage('Reschedule.php'); ?>">Reschedule</a></li>
        <li><a href="Cancel.php" class="nav__link <?php echo isCurrentPage('Cancel.php'); ?>">Cancel</a></li>
        <li><a href="dashboard.php" class="nav__link back-btn">Back to Dashboard</a></li>
        <li><a href="logout.php" class="nav__link logout-btn">Logout</a></li>
    </ul>
</nav> 