<?php
// Shared Session Configuration
// This file ensures consistent session storage path and lifetime across the application.

// 1. Set Session Lifetime (24 Hours)
$lifetime = 86400; // 24 hours
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);

// 2. Set Custom Session Path (Avoid hosting cleanup)
// Use absolute path relative to this file location (includes/)
// __DIR__ is .../cbt/includes
// dirname(__DIR__) is .../cbt
$session_save_path = dirname(__DIR__) . '/sessions';

if (!file_exists($session_save_path)) {
    @mkdir($session_save_path, 0777, true);
}

if (is_writable($session_save_path)) {
    session_save_path($session_save_path);
}
?>