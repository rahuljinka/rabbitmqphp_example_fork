<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sessionToken = $data['token'] ?? null;

if (!$sessionToken) {
    echo json_encode(['success' => false, 'message' => 'No session token provided.']);
    exit();
}

error_log("Attempting to log out with session token: $sessionToken");

try {
    $db = new mysqli("127.0.0.1", "appuser", "12345", "login");

    if ($db->connect_errno) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }

    $stmt = $db->prepare("DELETE FROM sessions WHERE session_token = ?");
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $db->error);
    }

    $stmt->bind_param("s", $sessionToken);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Logout successful.']);
            error_log("Session token $sessionToken successfully deleted.");
        } else {
            throw new Exception("Session token not found or already deleted.");
        }
    } else {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    $stmt->close();
    $db->close();

} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

