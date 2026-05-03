<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "spotify_mood_db";
    $port = 3307;
    
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql = "INSERT INTO users (password, username) VALUES ('$pass', '$user')";
    $result = $conn->query($sql);
    
 if ($conn->affected_rows > 0) {
    echo "Registration successful! Redirecting to the Login page";
    header("refresh:2;url=main.html");
} else {
    echo "Username already exists. Get more creative! <br> Redirecting to Register page";
header("refresh:2;url=register.html");
    }
    $conn->close();
}
?>