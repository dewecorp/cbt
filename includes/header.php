<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure database and base_url available
if (!isset($koneksi) || !isset($base_url)) {
    $config_path = dirname(__DIR__) . '/config/database.php';
    if (file_exists($config_path)) {
        include $config_path;
    }
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($base_url)) {
        echo "<script>window.location='" . $base_url . "index.php';</script>";
    } else {
        echo "<script>window.location='../../index.php';</script>";
    }
    exit;
}

// Robust Session Self-Healing
// Memastikan level dan data session selalu sinkron dengan database
if (isset($_SESSION['user_id']) && isset($koneksi)) {
    $uid_check = $_SESSION['user_id'];
    $current_level = isset($_SESSION['level']) ? $_SESSION['level'] : '';

    if ($current_level === 'siswa') {
        // Jika session bilang siswa, cek tabel siswa DULU
        $q_check_s = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa='$uid_check'");
        if (mysqli_num_rows($q_check_s) > 0) {
            // Valid siswa
            $s = mysqli_fetch_assoc($q_check_s);
            if (!isset($_SESSION['nama'])) $_SESSION['nama'] = $s['nama_siswa'];
            if (!isset($_SESSION['id_kelas'])) $_SESSION['id_kelas'] = $s['id_kelas'];
        } else {
            // Tidak ketemu di siswa? Cek users
            $q_check_u = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$uid_check'");
            if (mysqli_num_rows($q_check_u) > 0) {
                $d_check_u = mysqli_fetch_assoc($q_check_u);
                $_SESSION['level'] = $d_check_u['level'];
                if (!isset($_SESSION['nama'])) $_SESSION['nama'] = $d_check_u['nama_lengkap'];
            }
        }
    } else {
        // Jika session bilang admin/guru, atau kosong, cek users DULU
        $q_check_u = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$uid_check'");
        if (mysqli_num_rows($q_check_u) > 0) {
            $d_check_u = mysqli_fetch_assoc($q_check_u);
            // Perbaiki level jika tidak match (misal guru jadi admin atau sebaliknya)
            if ($current_level !== $d_check_u['level']) {
                $_SESSION['level'] = $d_check_u['level'];
            }
            if (!isset($_SESSION['nama'])) $_SESSION['nama'] = $d_check_u['nama_lengkap'];
            // Update foto session
            $_SESSION['foto'] = isset($d_check_u['foto']) ? $d_check_u['foto'] : null;
        } else {
            // Tidak ketemu di users? Cek siswa
            $q_check_s = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa='$uid_check'");
            if (mysqli_num_rows($q_check_s) > 0) {
                $s = mysqli_fetch_assoc($q_check_s);
                $_SESSION['level'] = 'siswa';
                if (!isset($_SESSION['nama'])) $_SESSION['nama'] = $s['nama_siswa'];
                if (!isset($_SESSION['id_kelas'])) $_SESSION['id_kelas'] = $s['id_kelas'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | E-Learning' : 'E-Learning'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_1770185899.png">
    
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="<?php echo $base_url; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables -->
    <link href="<?php echo $base_url; ?>assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?php echo $base_url; ?>assets/vendor/datatables/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link href="<?php echo $base_url; ?>assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo $base_url; ?>assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo $base_url; ?>assets/css/custom.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="<?php echo $base_url; ?>assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <script src="<?php echo $base_url; ?>assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
</head>
<body>
    <div id="overlay"></div>
    <div id="wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <?php include __DIR__ . '/navbar.php'; ?>
            
            <div class="container-fluid py-4">
