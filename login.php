<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    require_once('path.inc');
    require_once('get_host_info.inc');
    require_once('rabbitMQLib.inc');

    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

    $request = array();
    $request['type'] = "login";
    $request['username'] = $username;
    $request['password'] = $password;

    try {
        $response = $client->send_request($request);

        if ($response['returnCode'] === '0') {
            echo "<script>
                    sessionStorage.setItem('sessionToken', '{$response['sessionToken']}');
                    sessionStorage.setItem('username', '{$username}');
                    window.location.href = 'logged_in.php';
                  </script>";
            exit();
        } else {
            echo "<p>Login failed: " . $response['message'] . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Invalid request. Please try again.</p>";
}
?>

