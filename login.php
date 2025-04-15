<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric = $_POST['matric'];
    $conn = mysqli_connect("localhost", "root", "", "mydb");

    if ($conn) {
        $sql = "SELECT id, name, matric, email, picture FROM users WHERE matric = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $matric);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name']; 
            $_SESSION['matric'] = $user['matric'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            $_SESSION['picture'] = !empty($user['picture']) ? $user['picture'] : 'default-profile.png';

            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
            exit();
        } else {
                header('Location: dashboard.php');
                exit();
        }
    } else {
            echo "<p style='color: red;'>Invalid matric number.</p>";
    }

        $stmt->close();
        $conn->close();
    } else {
        echo "<p style='color: red;'>Database connection failed.</p>";
}
}
?>
