<?php
$host = "localhost";
$user = "root";         // Sesuaikan dengan user database Anda
$pass = "";             // Sesuaikan dengan password database Anda
$db   = "dbcybertasker";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
