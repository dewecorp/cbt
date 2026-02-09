<?php
session_name('CBT_GURU');
session_start();

// Ensure only guru access
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'guru') {
    header("Location: index.php");
    exit;
}

// Include the main dashboard logic
include 'dashboard.php';
?>
