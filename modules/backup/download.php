<?php
session_start();

// Cek Level Admin
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    die("Akses ditolak");
}

if (isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $filepath = __DIR__ . "/../../backups/" . $filename;

    if (file_exists($filepath)) {
        // Set headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Clear buffer output
        ob_clean();
        flush();
        
        readfile($filepath);
        exit;
    } else {
        echo "File tidak ditemukan: " . htmlspecialchars($filename);
    }
} else {
    echo "Tidak ada file yang diminta.";
}
?>