<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "cbt_sultan_fattah";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');
mysqli_query($koneksi, "SET time_zone = '+07:00'");

// Base URL configuration - adjust as needed
$base_url = "http://localhost/cbt/";
?>
