#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Generate a secure session token
function generateSessionToken() {
    return bin2hex(random_bytes(16));
}

// Handle login requests
function doLogin($username, $password) {
    $db = new mysqli("127.0.0.1", "appuser", "12345", "login");

    if ($db->connect_errno) {
        echo "Error: Database connection failed." . PHP_EOL;
        return array("returnCode" => '1', "message" => "Database connection failed");
    }

    $un = $db->real_escape_string($username);
    $pw = $db->real_escape_string($password);

    // Fetch the password from the database
    $query = "SELECT password FROM users WHERE username = '$un'";
    $result = $db->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if ($row["password"] === $pw) {
            echo "Login successful for $username" . PHP_EOL;

            // Generate a session token and store it in the database
            $sessionToken = generateSessionToken();
            $insertSessionQuery = "INSERT INTO sessions (username, session_token) 
                                   VALUES ('$un', '$sessionToken') 
                                   ON DUPLICATE KEY UPDATE session_token = '$sessionToken'";

            if ($db->query($insertSessionQuery) === TRUE) {
                return array(
                    "returnCode" => '0',
                    "message" => "Login successful",
                    "sessionToken" => $sessionToken
                );
            } else {
                return array("returnCode" => '1', "message" => "Failed to create session");
            }
        } else {
            return array("returnCode" => '1', "message" => "Incorrect password");
        }
    } else {
        return array("returnCode" => '1', "message" => "Username not found");
    }
}

// Handle registration requests
function doRegister($username, $password) {
    $db = new mysqli("127.0.0.1", "appuser", "12345", "login");

    if ($db->connect_errno) {
        return array("returnCode" => '1', "message" => "Database connection failed");
    }

    $un = $db->real_escape_string($username);
    $pw = $db->real_escape_string($password);

    $checkUserQuery = "SELECT * FROM users WHERE username = '$un'";
    $response = $db->query($checkUserQuery);

    if ($response && $response->num_rows > 0) {
        return array("returnCode" => '1', "message" => "Username already exists");
    }

    $insertUserQuery = "INSERT INTO users (username, password) VALUES ('$un', '$pw')";
    if ($db->query($insertUserQuery) === TRUE) {
        return array("returnCode" => '0', "message" => "Registration successful");
    } else {
        return array("returnCode" => '1', "message" => "Registration failed");
    }
}

// Validate a session token
function doValidateSession($sessionToken) {
    $db = new mysqli("127.0.0.1", "appuser", "12345", "login");

    if ($db->connect_errno) {
        return array("returnCode" => '1', "message" => "Database connection failed");
    }

    $token = $db->real_escape_string($sessionToken);
    $query = "SELECT * FROM sessions WHERE session_token = '$token'";
    $response = $db->query($query);

    if ($response && $response->num_rows > 0) {
        return array("returnCode" => '0', "message" => "Session valid");
    } else {
        return array("returnCode" => '1', "message" => "Invalid session");
    }
}

// Logout and delete the session token
function doLogout($sessionToken) {
    $db = new mysqli("127.0.0.1", "appuser", "12345", "login");

    if ($db->connect_errno) {
        return array("returnCode" => '1', "message" => "Database connection failed");
    }

    $token = $db->real_escape_string($sessionToken);
    $deleteQuery = "DELETE FROM sessions WHERE session_token = '$token'";

    if ($db->query($deleteQuery) === TRUE) {
        echo "Logout successful. Session deleted for token: $token" . PHP_EOL;
        return array("returnCode" => '0', "message" => "Logout successful");
    } else {
        return array("returnCode" => '1', "message" => "Failed to logout");
    }
}

// Process incoming RabbitMQ requests
function requestProcessor($request) {
    echo "Received request" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type'])) {
        return array("returnCode" => '1', "message" => "Invalid request type");
    }

    switch ($request['type']) {
        case "login":
            return doLogin($request['username'], $request['password']);
        case "register":
            return doRegister($request['username'], $request['password']);
        case "validate_session":
            return doValidateSession($request['sessionToken']);
        case "logout":
            return doLogout($request['sessionToken']);
        default:
            return array("returnCode" => '1', "message" => "Unsupported request type");
    }
}

// Start the RabbitMQ server
$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN" . PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END" . PHP_EOL;

exit();
?>

