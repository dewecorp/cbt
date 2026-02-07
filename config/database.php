<?php
$host = "127.0.0.1";
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

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    level VARCHAR(20) NULL,
    action VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    details VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($koneksi, "DELETE FROM activity_log WHERE created_at < (NOW() - INTERVAL 1 DAY)");

if (!function_exists('log_activity')) {
function log_activity($action, $module, $details = '') {
    global $koneksi;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
    $level = isset($_SESSION['level']) ? $_SESSION['level'] : null;
    $uid = $user_id !== null ? (int)$user_id : 'NULL';
    $u = $username !== null ? "'" . mysqli_real_escape_string($koneksi, $username) . "'" : 'NULL';
    $lv = $level !== null ? "'" . mysqli_real_escape_string($koneksi, $level) . "'" : 'NULL';
    $ac = mysqli_real_escape_string($koneksi, $action);
    $md = mysqli_real_escape_string($koneksi, $module);
    $dt = mysqli_real_escape_string($koneksi, $details);
    $sql = "INSERT INTO activity_log (user_id, username, level, action, module, details, created_at) VALUES ($uid, $u, $lv, '$ac', '$md', '$dt', NOW())";
    mysqli_query($koneksi, $sql);
}
}

if (!function_exists('time_ago_str')) {
function time_ago_str($datetime) {
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return $diff . " detik lalu";
    if ($diff < 3600) return floor($diff / 60) . " menit lalu";
    if ($diff < 86400) return floor($diff / 3600) . " jam lalu";
    return floor($diff / 86400) . " hari lalu";
}
}
?>
