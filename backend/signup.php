<?php
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

    $query = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
    if (mysqli_query($conn, $query)) {
        echo "Registration successful!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>
