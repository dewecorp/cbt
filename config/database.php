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

// Base URL configuration - dynamically menyesuaikan host (cbt.test / localhost)
if (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    if ($host === 'localhost') {
        $base_url = $scheme . $host . "/cbt/";
    } else {
        $base_url = $scheme . $host . "/";
    }
} else {
    // Fallback ketika dijalankan via CLI
    $base_url = "http://localhost/cbt/";
}

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

mysqli_query($koneksi, "DELETE FROM notifications WHERE created_at < (NOW() - INTERVAL 1 DAY)");

// --- AUTO MIGRATION SECTION ---
// Check and add columns for 'users' table
$check_users_jk = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'jk'");
if (mysqli_num_rows($check_users_jk) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN jk ENUM('L', 'P') AFTER nama_lengkap");
}
$check_users_plain = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'password_plain'");
if (mysqli_num_rows($check_users_plain) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN password_plain VARCHAR(100) AFTER password");
}
$check_users_foto = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'foto'");
if (mysqli_num_rows($check_users_foto) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN foto VARCHAR(255) AFTER nama_lengkap");
}
$check_users_mk = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'mengajar_kelas'");
if (mysqli_num_rows($check_users_mk) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN mengajar_kelas TEXT NULL");
}
$check_users_mm = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'mengajar_mapel'");
if (mysqli_num_rows($check_users_mm) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN mengajar_mapel TEXT NULL");
}

// Check and add columns for 'siswa' table
$check_siswa_nisn = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa LIKE 'nisn'");
if (mysqli_num_rows($check_siswa_nisn) == 0) {
    // Check if 'nis' exists to rename it, otherwise just add 'nisn'
    $check_nis = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa LIKE 'nis'");
    if (mysqli_num_rows($check_nis) > 0) {
        mysqli_query($koneksi, "ALTER TABLE siswa CHANGE nis nisn VARCHAR(20) NOT NULL");
    } else {
        mysqli_query($koneksi, "ALTER TABLE siswa ADD COLUMN nisn VARCHAR(20) NOT NULL AFTER id_siswa");
    }
}
$check_siswa_tpl = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa LIKE 'tempat_lahir'");
if (mysqli_num_rows($check_siswa_tpl) == 0) {
    mysqli_query($koneksi, "ALTER TABLE siswa ADD COLUMN tempat_lahir VARCHAR(100) AFTER nama_siswa");
}
$check_siswa_tgl = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa LIKE 'tanggal_lahir'");
if (mysqli_num_rows($check_siswa_tgl) == 0) {
    mysqli_query($koneksi, "ALTER TABLE siswa ADD COLUMN tanggal_lahir DATE AFTER tempat_lahir");
}
$check_siswa_jk = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa LIKE 'jk'");
if (mysqli_num_rows($check_siswa_jk) == 0) {
    mysqli_query($koneksi, "ALTER TABLE siswa ADD COLUMN jk ENUM('L', 'P') AFTER tanggal_lahir");
}
$check_siswa_foto = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa LIKE 'foto'");
if (mysqli_num_rows($check_siswa_foto) == 0) {
    mysqli_query($koneksi, "ALTER TABLE siswa ADD COLUMN foto VARCHAR(255) AFTER jk");
}
// ------------------------------

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS simad_sync_cursor (
    sync_type VARCHAR(32) NOT NULL,
    cursor_since VARCHAR(19) DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (sync_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$check_users_simad_guru = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'simad_id_guru'");
if (mysqli_num_rows($check_users_simad_guru) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN simad_id_guru INT NULL DEFAULT NULL AFTER level");
}

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

if (!defined('SIMAD_STUDENT_API_URL')) {
    define('SIMAD_STUDENT_API_URL', 'https://simad.misultanfattah.sch.id/api/v1/students.php?api_key=SIS_CENTRAL_HUB_SECRET_2026');
}
if (!defined('SIMAD_TEACHER_API_URL')) {
    define('SIMAD_TEACHER_API_URL', 'https://simad.misultanfattah.sch.id/api/v1/teachers.php?api_key=SIS_CENTRAL_HUB_SECRET_2026');
}
?>
