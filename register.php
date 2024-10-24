<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    // Capture the input from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    require_once('path.inc');
    require_once('get_host_info.inc');
    require_once('rabbitMQLib.inc');

    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

    $request = array();
    $request['type'] = "register";
    $request['username'] = $username;
    $request['password'] = $password;

    try {
        $response = $client->send_request($request);
        if ($response['returnCode'] === '0') {
            echo "<p>Registration successful! Redirecting to login page...</p>";
            header('Refresh: 5; URL=login.html');  // Redirect to login.html after 5 seconds
            exit();
        } else {
            echo "<p>Registration failed: " . htmlspecialchars($response['message']) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error communicating with the RabbitMQ server: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Invalid request. Please try again.</p>";
}
?>

