<?php
include '../../includes/init_session.php';
include '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url);
    exit;
}

if (isset($_GET['all']) && $_GET['all'] == '1') {
    $user_id = $_SESSION['user_id'];
    mysqli_query($koneksi, "UPDATE notifications SET is_read=1 WHERE user_id='$user_id'");
    header("Location: " . $base_url . "dashboard.php");
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
        
        // Append role parameter to maintain session
        $role = isset($_SESSION['level']) ? $_SESSION['level'] : '';
        if ($role) {
            if (strpos($link, '?') !== false) {
                $link .= '&role=' . $role;
            } else {
                $link .= '?role=' . $role;
            }
        }
        
        // Redirect
        header("Location: " . $base_url . $link);
        exit;
    }
}

// Fallback if error or not found
header("Location: " . $base_url . "dashboard.php");
exit;
?>
