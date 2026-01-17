<?php
$host = "localhost";
$user = "root";
$pass = ""; // XAMPP এ পাসওয়ার্ড ফাঁকা থাকে
$dbname = "rajtech_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>