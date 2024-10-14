#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('login.php.inc'); // Include the login validation script

// Modify doLogin to validate against the local database
function doLogin($username, $password)
{
    // Create an instance of loginDB class to connect to the database
    $login = new loginDB();
    
    // Use the validateLogin function to verify the username and password
    $isValid = $login->validateLogin($username, $password);
    if ($isValid) {
        return array("returnCode" => 0, "message" => "Login successful");
    } else {
        return array("returnCode" => 1, "message" => "Login failed");
    }
}

function requestProcessor($request)
{
    echo "received request" . PHP_EOL;
    var_dump($request);
    
    if (!isset($request['type'])) {
        return "ERROR: unsupported message type";
    }

    switch ($request['type']) {
        case "login":
            return doLogin($request['username'], $request['password']);
        case "validate_session":
            return doValidate($request['sessionId']);
        default:
            return "ERROR: unsupported message type";
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

