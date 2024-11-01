<?php
$db = new mysqli("127.0.0.1", "root", "12345", "login");
if ($db->connect_errno) {
    die("Failed to connect to MySQL: " . $db->connect_error);
}
echo "Successfully connected!";
?>

