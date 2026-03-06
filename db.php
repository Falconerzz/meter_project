<?php
$host = 'localhost';
$user = 'root'; // เปลี่ยนให้ตรงกับของคุณ
$pass = '';     // เปลี่ยนให้ตรงกับของคุณ
$db   = 'cyberss_iot';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>