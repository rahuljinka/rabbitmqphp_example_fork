<?php
class loginDB {
    private $logindb;

    public function __construct() {
        // Connect to the MySQL database on VM #3
        $this->logindb = new mysqli("127.0.0.1", "root", "12345", "login");

        if ($this->logindb->connect_errno != 0) {
            echo "Error connecting to database: " . $this->logindb->connect_error . PHP_EOL;
            exit(1);
        }
        echo "Connected to database" . PHP_EOL;
    }

    public function validateLogin($username, $password) {
        $un = $this->logindb->real_escape_string($username);
        $pw = $this->logindb->real_escape_string($password);

        $statement = "SELECT * FROM users WHERE screenname='$un'";
        $response = $this->logindb->query($statement);

        while ($row = $response->fetch_assoc()) {
            echo "checking password for $username" . PHP_EOL;
            if ($row['password'] === $pw) {
                echo "passwords match for $username" . PHP_EOL;
                return true;  // password match
            }
        }
        echo "password did not match for $username" . PHP_EOL;
        return false;  // no users matched username
    }
}
?>

