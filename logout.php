<?php
$role = isset($_GET['role']) ? $_GET['role'] : '';

// Set session name based on role to destroy the correct session
if ($role == 'admin') {
    session_name('CBT_ADMIN');
} elseif ($role == 'guru') {
    session_name('CBT_GURU');
} elseif ($role == 'siswa') {
    session_name('CBT_SISWA');
}
// If no role specified, it might use PHPSESSID or fail to find session. 
// Ideally we should try to detect or loop, but for now we rely on the link providing the role.

session_start();
include 'config/database.php';

// Only log if session is valid
if (isset($_SESSION['user_id'])) {
    log_activity('logout', 'auth', 'logout');
}

session_destroy();
header("Location: index.php");
exit;
?>
