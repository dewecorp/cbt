<?php
include 'includes/init_session.php';
// Fallback if init_session didn't start a session (shouldn't happen if role is passed, but safe to keep)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'config/database.php';

// Jika belum login, kembalikan ke index
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Determine Redirect URL based on Level
$level = $_SESSION['level'] ?? '';
$redirect_url = 'dashboard.php'; // Default fallback

if ($level === 'admin') {
    $redirect_url = 'admin.php?role=admin';
} elseif ($level === 'guru') {
    $redirect_url = 'teacher.php?role=guru';
} elseif ($level === 'siswa') {
    $redirect_url = 'student.php?role=siswa';
} else {
    $redirect_url = 'dashboard.php?role=' . $level;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Berhasil - E-Learning</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    
    <script src="assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Login Berhasil',
                text: 'Selamat datang, <?php echo $_SESSION['nama']; ?>! Sedang mengalihkan ke dashboard...',
                showConfirmButton: false,
                timer: 2000,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                window.location.href = '<?php echo $redirect_url; ?>';
            });
        });
    </script>
    <?php session_write_close(); ?>
</body>
</html>