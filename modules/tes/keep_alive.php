<?php
// Script ini hanya untuk menjaga sesi tetap aktif (heartbeat)
// Dipanggil via AJAX dari halaman ujian

// Include init_session yang sudah kita modifikasi
// Path relatif dari modules/tes/keep_alive.php ke includes/init_session.php
include '../../includes/init_session.php';

// Pastikan session dimulai (jika belum oleh init_session)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Update last_activity (sudah dilakukan di init_session.php, tapi kita pastikan lagi)
$_SESSION['last_activity'] = time();

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Session refreshed', 'timestamp' => time()]);
?>
