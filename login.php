<?php
session_start();
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];

    $query = "SELECT id, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        header("Location: dashboard.php"); 
        exit();
    } else {
        echo "Invalid email or password!";
    }

    $stmt->close();
}

$conn->close();
?>
