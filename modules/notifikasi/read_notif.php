<?php
session_start();
include '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url);
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Get notification link and verify ownership
    $q = mysqli_query($koneksi, "SELECT link FROM notifications WHERE id='$id' AND user_id='$user_id'");
    
    if (mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        $link = $row['link'];
        
        // Mark as read
        mysqli_query($koneksi, "UPDATE notifications SET is_read=1 WHERE id='$id'");
        
        // Redirect
        header("Location: " . $base_url . $link);
        exit;
    }
}

// Fallback if error or not found
header("Location: " . $base_url . "dashboard.php");
exit;
?>