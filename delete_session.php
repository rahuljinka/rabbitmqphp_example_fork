<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sessionToken = $data['token'];

$db = new mysqli("127.0.0.1", "root", "12345", "login");

$stmt = $db->prepare("DELETE FROM sessions WHERE session_token = ?");
$stmt->bind_param("s", $sessionToken);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>

