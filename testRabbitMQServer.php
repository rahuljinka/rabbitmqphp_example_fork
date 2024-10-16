#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('login.php.inc'); // Include the login validation script

// Function to handle user login by checking the database for valid credentials
function doLogin($username, $password)
{
    $db = new mysqli("127.0.0.1", "root", "12345", "login");

    if ($db->connect_errno != 0) {
        echo "Error connecting to database: " . $db->connect_error . PHP_EOL;
        return array("returnCode" => '1', "message" => "Error connecting to database");
    }

    // Escape the input to prevent SQL injection
    $un = $db->real_escape_string($username);
    $pw = $db->real_escape_string($password);

    // Check for the user in the database
    $query = "SELECT * FROM users WHERE screenname = '$un'";
    $response = $db->query($query);

    if ($response->num_rows > 0) {
        $row = $response->fetch_assoc();
        echo "Stored password: '" . $row["password"] . "'" . PHP_EOL;
        echo "Input password: '" . $pw . "'" . PHP_EOL;

        if ($row["password"] === $pw) { // Exact match
            echo "passwords match for $username" . PHP_EOL;
            return array("returnCode" => '0', "message" => "Login successful");
        } else {
            echo "passwords did not match for $username" . PHP_EOL;
            return array("returnCode" => '1', "message" => "Incorrect password");
        }
    } else {
        echo "no user found with username $username" . PHP_EOL;
        return array("returnCode" => '1', "message" => "Username not found");
    }
}

// Function to handle user registration by inserting new credentials into the database
function doRegister($username, $password) {
    $db = new mysqli("127.0.0.1", "root", "12345", "login");

    // Check for database connection errors
    if ($db->connect_errno != 0) {
        echo "Error connecting to database: " . $db->connect_error . PHP_EOL;
        return array("returnCode" => '1', "message" => "Error connecting to database");
    }

    // Escape the input to prevent SQL injection
    $un = $db->real_escape_string($username);
    $pw = $db->real_escape_string($password);

    // Check if the user already exists
    $checkUserQuery = "SELECT * FROM users WHERE screenname = '$un'";
    $response = $db->query($checkUserQuery);

    if ($response->num_rows > 0) {
        return array("returnCode" => '1', "message" => "Username already exists");
    }

    // Insert the new user into the database
    $insertUserQuery = "INSERT INTO users (screenname, password) VALUES ('$un', '$pw')";
    if ($db->query($insertUserQuery) === TRUE) {
        return array("returnCode" => '0', "message" => "Registration successful");
    } else {
        return array("returnCode" => '1', "message" => "Error registering user");
    }
}

// Function to process incoming requests from RabbitMQ (login, register, etc.)
function requestProcessor($request)
{
    echo "received request" . PHP_EOL;
    var_dump($request);
    
    // Check for valid request type
    if (!isset($request['type'])) {
        return "ERROR: unsupported message type";
    }

    // Handle different types of requests (login, register, session validation)
    switch ($request['type']) {
        case "login":
            return doLogin($request['username'], $request['password']);
        case "validate_session":
            return doValidate($request['sessionId']);
        case "register":
            return doRegister($request['username'], $request['password']);
    }

    return array("returnCode" => '0', 'message' => "Server received request and processed");
}

// Create an instance of rabbitMQServer to connect to VM #1 (the RabbitMQ broker)
$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN" . PHP_EOL;

// Start processing incoming requests
$server->process_requests('requestProcessor');

echo "testRabbitMQServer END" . PHP_EOL;
exit();
?>

