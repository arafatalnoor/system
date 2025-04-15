<?php
$conn = mysqli_connect("localhost", "root", "", "mydb");
if ($conn) {
    $sql = "INSERT INTO notifications (matric, title, message, status) VALUES (?, ?, ?, 'unread')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $matric, $title, $message);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>

<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "mydb");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $matric = htmlspecialchars($_POST['matric']);
    $title = htmlspecialchars($_POST['title']);
    $message = htmlspecialchars($_POST['message']);

    // Insert data into notifications table
    $sql = "INSERT INTO notifications (matric, title, message, status) VALUES (?, ?, ?, 'unread')";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $matric, $title, $message);
        if ($stmt->execute()) {
            echo "Notification added successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing the query: " . $conn->error;
    }
}

// Close connection
$conn->close();
?>

