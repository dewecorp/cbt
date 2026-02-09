<?php
session_name('CBT_ADMIN');
session_start();

// Ensure only admin access
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Include the main dashboard logic
include 'dashboard.php';
?>
