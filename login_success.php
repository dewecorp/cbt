<?php
include 'includes/init_session.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$level = $_SESSION['level'] ?? '';
$redirect_url = 'dashboard.php';

if ($level === 'admin') {
    $redirect_url = 'admin.php?role=admin';
} elseif ($level === 'guru') {
    $redirect_url = 'teacher.php?role=guru';
} elseif ($level === 'siswa') {
    $redirect_url = 'student.php?role=siswa';
} else {
    $redirect_url = 'dashboard.php?role=' . $level;
}

header("Location: $redirect_url");
exit;
