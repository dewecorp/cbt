<?php
include 'includes/init_session.php';

// Ensure only guru access
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'guru') {
    header("Location: index.php");
    exit;
}

// Include the main dashboard logic
include 'dashboard.php';
?>
