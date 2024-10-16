<?php

class loginDB
{
    private $logindb;

    public function __construct()
    {
        // Connect to the MySQL database
        $this->logindb = new mysqli("127.0.0.1", "root", "12345", "login");

        if ($this->logindb->connect_errno != 0) {
            echo "Error connecting to database: " . $this->logindb->connect_error . PHP_EOL;
            exit(1);
        }
        echo "correctly connected to database" . PHP_EOL;
    }

    // Check if user already exists in the database
    public function checkUserExists($username)
    {
        $un = $this->logindb->real_escape_string($username);
        $statement = "SELECT * FROM users WHERE screenname = '$un'";
        $response = $this->logindb->query($statement);

        return $response->num_rows > 0; // Returns true if username exists
    }

    // Register a new user in the database
    public function registerUser($username, $password)
    {
        $un = $this->logindb->real_escape_string($username);
        // Hash the password before storing it
        $pw = password_hash($password, PASSWORD_DEFAULT);
        $statement = "INSERT INTO users (screenname, password) VALUES ('$un', '$pw')";

        return $this->logindb->query($statement);
    }

    // Validate login credentials
    public function validateLogin($username, $password)
    {
        $un = $this->logindb->real_escape_string($username);
        $statement = "SELECT * FROM users WHERE screenname = '$un'";
        $response = $this->logindb->query($statement);

        while ($row = $response->fetch_assoc()) {
            // Verify the password using password_verify()
            if (password_verify($password, $row["password"])) {
                echo "passwords match for $username" . PHP_EOL;
                return 1; // Password match
            }
            echo "passwords did not match for $username" . PHP_EOL;
        }
        return 0; // No matching user or incorrect password
    }
}
?>

