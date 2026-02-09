<?php
include '../../includes/init_session.php';
include '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access Denied');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';

if ($id > 0) {
    $query = mysqli_query($koneksi, "SELECT * FROM materials WHERE id_material = $id");
    if ($row = mysqli_fetch_assoc($query)) {
        if ($row['tipe'] === 'link') {
            header("Location: " . $row['path']);
            exit;
        }

        $baseDir = dirname(__DIR__, 2);
        $filePath = $baseDir . '/' . $row['path'];

        if (file_exists($filePath)) {
            // Disable caching
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");

            // Base64 Mode to bypass IDM (IDM ignores text/plain)
            if ($mode === 'base64') {
                header('Content-Type: text/plain');
                echo base64_encode(file_get_contents($filePath));
                exit;
            }

            // Standard binary mode
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $contentType = 'application/octet-stream';
            
            switch ($ext) {
                // FORCE application/octet-stream for PDF in standard mode too, just in case
                case 'pdf': $contentType = 'application/octet-stream'; break; 
                case 'jpg': 
                case 'jpeg': $contentType = 'image/jpeg'; break;
                case 'png': $contentType = 'image/png'; break;
                case 'mp4': $contentType = 'video/mp4'; break;
            }

            header('Content-Type: ' . $contentType);
            header('Content-Length: ' . filesize($filePath));
            
            readfile($filePath);
            exit;
        }
    }
}

http_response_code(404);
echo "File not found.";
