<?php
// Database connection code
$host = 'localhost';
$db = 'fyp_db';  // Replace with your database name
$user = 'root';   // Your MySQL username
$pass = '';       // Your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
