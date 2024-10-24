<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sessionToken = $data['token'];

$db = new mysqli("127.0.0.1", "appuser", "12345", "login");

$stmt = $db->prepare("SELECT * FROM sessions WHERE session_token = ?");
$stmt->bind_param("s", $sessionToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['valid' => true]);
} else {
    echo json_encode(['valid' => false]);
}
?>

