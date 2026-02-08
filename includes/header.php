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

// Ensure level is set (Session Validation)
if (isset($_SESSION['user_id']) && !isset($_SESSION['level'])) {
    // If level is missing, session is corrupt. Destroy and redirect.
    session_destroy();
    echo "<script>window.location='index.php';</script>";
    exit;
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
