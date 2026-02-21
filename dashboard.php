<?php
include 'config/database.php';
$page_title = 'Dashboard';
include 'includes/header.php';

// Helper Functions (Used by modules)
// time_ago_str moved to config/database.php

function get_indo_day($date) {
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    return $days[date('l', strtotime($date))];
}

// Safe session level
$level = isset($_SESSION['level']) ? $_SESSION['level'] : '';

// Validation: If user_id exists but level is missing, force logout to prevent errors
if (isset($_SESSION['user_id']) && empty($level)) {
    session_destroy();
    echo "<script>window.location='index.php';</script>";
    exit;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <!-- Clock moved to navbar -->
        </div>
    </div>

    <?php
    if ($level === 'admin') {
        include 'modules/dashboard/admin.php';
    } elseif ($level === 'guru') {
        include 'modules/dashboard/guru.php';
    } elseif ($level === 'siswa') {
        include 'modules/dashboard/siswa.php';
    } else {
        echo '<div class="alert alert-warning">Role tidak dikenali or Session expired. Silahkan login ulang.</div>';
    }
    ?>

</div>

<?php include 'includes/footer.php'; ?>
