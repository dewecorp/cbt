<?php
include 'includes/init_session.php';

include 'config/database.php';

// Only log if session is valid
if (isset($_SESSION['user_id'])) {
    log_activity('logout', 'auth', 'logout');
}

// Destroy session data
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: index.php");
exit;
?>
