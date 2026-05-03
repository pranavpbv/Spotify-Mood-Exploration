<?php

session_start();


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spotify_mood_db";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$user = $_POST['username'];
$pass = $_POST['password'];

$sql = "SELECT * FROM users WHERE username='$user' AND password='$pass'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $_SESSION['username'] = $user; 
    header("refresh:0;url=pershome.php");
} else {
    echo "Invalid username or password.";
    header("refresh:2;url=main.html");
}
}
$conn->close();
?>