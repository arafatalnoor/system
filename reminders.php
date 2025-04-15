<?php
$conn = mysqli_connect("localhost", "root", "", "mydb");
if ($conn) {
    $sql = "INSERT INTO reminders (matric, reminder_text) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $matric, $reminder_text);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>
