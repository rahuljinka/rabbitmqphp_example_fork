<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $db = new mysqli("127.0.0.1", "root", "12345", "login");

    if ($db->connect_errno) {
        echo "Error: Could not connect to database.";
        exit();
    }

    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        echo "
        <script>
            alert('Registration successful! Redirecting to login page...');
            window.location.href = 'login.html';
        </script>";
    } else {
        echo "Error: Could not register user.";
    }

    $stmt->close();
    $db->close();
} else {
    echo "Invalid request.";
}
?>

