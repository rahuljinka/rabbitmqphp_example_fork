<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const sessionToken = sessionStorage.getItem('sessionToken');
            const username = sessionStorage.getItem('username');

            if (!sessionToken) {
                alert("Invalid session. Redirecting to login page...");
                window.location.href = 'login.html';
                return;
            }

            document.getElementById('welcome-message').innerText = `Hello, ${username}`;
        });

        function logout() {
            const sessionToken = sessionStorage.getItem('sessionToken');

            if (!sessionToken) {
                alert("No active session found. Redirecting to login...");
                window.location.href = 'login.html';
                return;
            }

            fetch('logout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: sessionToken })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.clear(); // Clear all session storage
                    alert("Logout successful. Redirecting to login page...");
                    window.location.href = 'login.html';
                } else {
                    alert("Error during logout. Please try again.");
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                alert("An error occurred during logout. Please try again.");
            });
        }
    </script>
</head>
<body>
    <h1 id="welcome-message">Loading...</h1>
    <button onclick="logout()">Logout</button>
</body>
</html>

