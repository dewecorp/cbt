<?php
include 'config/database.php';
$page_title = 'Dashboard';
include 'includes/header.php';

if (isset($_SESSION['login_success'])) {
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Proses Authentication Berhasil',
            text: 'Akun anda berhasil diverifikasi',
            showConfirmButton: false,
            timer: 1600
        });
    </script>";
    unset($_SESSION['login_success']);
}

// Helper Functions (Used by modules)
function time_ago_str($datetime) {
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return $diff . " detik lalu";
    if ($diff < 3600) return floor($diff / 60) . " menit lalu";
    if ($diff < 86400) return floor($diff / 3600) . " jam lalu";
    return floor($diff / 86400) . " hari lalu";
}

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

// Self-healing session level mismatch (Fix for Khoiruddin & Adiba case)
if (isset($_SESSION['user_id'])) {
    $uid_check = $_SESSION['user_id'];
    $current_level = isset($_SESSION['level']) ? $_SESSION['level'] : '';

    if ($current_level === 'siswa') {
        // Jika session bilang siswa, cek tabel siswa DULU
        $q_check_s = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa='$uid_check'");
        if (mysqli_num_rows($q_check_s) > 0) {
            // Valid siswa, jangan ubah apa-apa
        } else {
            // Tidak ketemu di siswa? Mungkin salah label, baru cek users
            $q_check_u = mysqli_query($koneksi, "SELECT level FROM users WHERE id_user='$uid_check'");
            if (mysqli_num_rows($q_check_u) > 0) {
                $d_check_u = mysqli_fetch_assoc($q_check_u);
                $_SESSION['level'] = $d_check_u['level'];
                $level = $d_check_u['level'];
            }
        }
    } else {
        // Jika session bilang admin/guru, atau kosong, cek users DULU
        $q_check_u = mysqli_query($koneksi, "SELECT level FROM users WHERE id_user='$uid_check'");
        if (mysqli_num_rows($q_check_u) > 0) {
            $d_check_u = mysqli_fetch_assoc($q_check_u);
            // Disabled to fix Guru-to-Admin role switching
            /*
            if ($current_level !== $d_check_u['level']) {
                $_SESSION['level'] = $d_check_u['level'];
                $level = $d_check_u['level'];
            }
            */
        } else {
            // Tidak ketemu di users? Cek siswa
            $q_check_s = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa='$uid_check'");
            if (mysqli_num_rows($q_check_s) > 0) {
                $_SESSION['level'] = 'siswa';
                $level = 'siswa';
            }
        }
    }
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
