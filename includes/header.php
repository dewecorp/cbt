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
    header("Location: " . (isset($base_url) ? $base_url : "/") . "index.php");
    exit;
}

// Recover missing session level if needed
if (!isset($_SESSION['level']) && isset($koneksi)) {
    $uid = $_SESSION['user_id'];
    $q_u = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$uid'");
    if ($q_u && mysqli_num_rows($q_u) > 0) {
        $u = mysqli_fetch_assoc($q_u);
        $_SESSION['level'] = $u['level'];
        if (!isset($_SESSION['nama'])) {
            $_SESSION['nama'] = $u['nama_lengkap'];
        }
    } else {
        $q_s = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa='$uid'");
        if ($q_s && mysqli_num_rows($q_s) > 0) {
            $s = mysqli_fetch_assoc($q_s);
            $_SESSION['level'] = 'siswa';
            if (!isset($_SESSION['nama'])) {
                $_SESSION['nama'] = $s['nama_siswa'];
            }
            if (!isset($_SESSION['id_kelas'])) {
                $_SESSION['id_kelas'] = $s['id_kelas'];
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
    <title>CBT MI Sultan Fattah Sukosono</title>
    
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo $base_url; ?>assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo $base_url; ?>assets/css/custom.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="overlay"></div>
    <div id="wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <?php include __DIR__ . '/navbar.php'; ?>
            
            <div class="container-fluid py-4">
