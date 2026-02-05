<?php
session_start();
include 'config/database.php';
log_activity('logout', 'auth', 'logout');
session_destroy();
header("Location: index.php");
exit;
