<?php
session_name('CBT_SISWA');
session_start();
include 'config/database.php';
$page_title = 'Dashboard Siswa';

include 'includes/header.php';

// Helper Functions (if not in database.php)
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

$level = isset($_SESSION['level']) ? $_SESSION['level'] : '';

// Validation
if (isset($_SESSION['user_id']) && empty($level)) {
    session_destroy();
    echo "<script>window.location='index.php';</script>";
    exit;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard Siswa</h1>
    </div>

    <?php
    if ($level === 'siswa') {
        include 'modules/dashboard/siswa.php';
    } else {
        echo '<div class="alert alert-warning">Role tidak sesuai. Halaman ini khusus Siswa.</div>';
    }
    ?>

</div>

<?php include 'includes/footer.php'; ?>
