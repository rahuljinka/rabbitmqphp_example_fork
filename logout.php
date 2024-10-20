<?php
header('Content-Type: application/json');

// Retrieve session token from request
$data = json_decode(file_get_contents('php://input'), true);
$sessionToken = $data['token'] ?? null;

if (!$sessionToken) {
    echo json_encode(['success' => false, 'message' => 'Invalid session token.']);
    exit();
}

// Database connection
try {
    $db = new mysqli("127.0.0.1", "root", "12345", "login");

    if ($db->connect_errno) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }

    // Prepare and execute query to delete the session
    $stmt = $db->prepare("DELETE FROM sessions WHERE session_token = ?");
    if (!$stmt) {
        throw new Exception("Statement preparation failed: " . $db->error);
    }

    $stmt->bind_param("s", $sessionToken);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    } else {
        throw new Exception("Failed to delete session token: " . $stmt->error);
    }

    $stmt->close();
    $db->close();

} catch (Exception $e) {
    // Handle and log the exception
    error_log($e->getMessage());  // Log error to Apache logs
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

