<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // Change this if your MySQL username is different
$password = ""; // Change this if your MySQL password is different
$database = "final_year_project";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Encrypt password

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $_SESSION['username'] = $username;
        header("Location: dashboard.php"); // Redirect to dashboard after successful login
    } else {
        echo "Invalid username or password!";
    }
}

mysqli_close($conn);
?>
