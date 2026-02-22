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

$hero_image = '';
if (isset($koneksi)) {
    $q_setting_hero = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
    if ($q_setting_hero && mysqli_num_rows($q_setting_hero) > 0) {
        $row_setting_hero = mysqli_fetch_assoc($q_setting_hero);
        if (isset($row_setting_hero['hero_image'])) {
            $hero_image = $row_setting_hero['hero_image'];
        }
    }
}

$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pengguna';
$role_param = $level ? '?role=' . $level : '';
$dashboard_url = 'dashboard.php' . $role_param;
if ($level === 'admin') {
    $dashboard_url = 'admin.php' . $role_param;
} elseif ($level === 'guru') {
    $dashboard_url = 'teacher.php' . $role_param;
} elseif ($level === 'siswa') {
    $dashboard_url = 'student.php' . $role_param;
}

$menu_items = [];
if ($level === 'admin') {
    $menu_items = [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'url' => $base_url . $dashboard_url],
        ['icon' => 'fa-chalkboard-teacher', 'label' => 'Data Guru', 'url' => $base_url . 'modules/master/guru.php' . $role_param],
        ['icon' => 'fa-user-graduate', 'label' => 'Data Siswa', 'url' => $base_url . 'modules/master/siswa.php' . $role_param],
        ['icon' => 'fa-school', 'label' => 'Data Kelas', 'url' => $base_url . 'modules/master/kelas.php' . $role_param],
        ['icon' => 'fa-book', 'label' => 'Mata Pelajaran', 'url' => $base_url . 'modules/master/mapel.php' . $role_param],
        ['icon' => 'fa-layer-group', 'label' => 'Kelas Online', 'url' => $base_url . 'modules/elearning/courses.php' . $role_param],
        ['icon' => 'fa-book-open', 'label' => 'Materi', 'url' => $base_url . 'modules/elearning/materials.php' . $role_param],
        ['icon' => 'fa-tasks', 'label' => 'Tugas', 'url' => $base_url . 'modules/elearning/assignments.php' . $role_param],
        ['icon' => 'fa-comments', 'label' => 'Forum', 'url' => $base_url . 'modules/elearning/forum.php' . $role_param],
        ['icon' => 'fa-database', 'label' => 'Bank Soal', 'url' => $base_url . 'modules/tes/bank_soal.php' . $role_param],
        ['icon' => 'fa-calendar-alt', 'label' => 'Jadwal Asesmen', 'url' => $base_url . 'modules/tes/jadwal_ujian.php' . $role_param],
        ['icon' => 'fa-poll', 'label' => 'Hasil Asesmen', 'url' => $base_url . 'modules/tes/hasil_ujian.php' . $role_param],
        ['icon' => 'fa-file-alt', 'label' => 'Rekap Nilai', 'url' => $base_url . 'modules/tes/rekap_nilai.php' . $role_param],
        ['icon' => 'fa-print', 'label' => 'Kartu Ujian', 'url' => $base_url . 'modules/cetak/kartu_ujian.php' . $role_param],
        ['icon' => 'fa-cogs', 'label' => 'Pengaturan', 'url' => $base_url . 'modules/pengaturan/index.php' . $role_param],
        ['icon' => 'fa-users-cog', 'label' => 'Pengguna', 'url' => $base_url . 'modules/users/index.php' . $role_param],
        ['icon' => 'fa-hdd', 'label' => 'Backup', 'url' => $base_url . 'modules/backup/index.php' . $role_param],
    ];
} elseif ($level === 'guru') {
    $menu_items = [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'url' => $base_url . $dashboard_url],
        ['icon' => 'fa-layer-group', 'label' => 'Kelas Online', 'url' => $base_url . 'modules/elearning/courses.php' . $role_param],
        ['icon' => 'fa-book-open', 'label' => 'Materi', 'url' => $base_url . 'modules/elearning/materials.php' . $role_param],
        ['icon' => 'fa-tasks', 'label' => 'Tugas', 'url' => $base_url . 'modules/elearning/assignments.php' . $role_param],
        ['icon' => 'fa-calendar-check', 'label' => 'Kehadiran', 'url' => $base_url . 'modules/elearning/rekap_absensi.php' . $role_param],
        ['icon' => 'fa-comments', 'label' => 'Forum', 'url' => $base_url . 'modules/elearning/forum.php' . $role_param],
        ['icon' => 'fa-calendar-alt', 'label' => 'Jadwal Asesmen', 'url' => $base_url . 'modules/tes/jadwal_ujian.php' . $role_param],
        ['icon' => 'fa-poll', 'label' => 'Hasil Asesmen', 'url' => $base_url . 'modules/tes/hasil_ujian.php' . $role_param],
    ];
} elseif ($level === 'siswa') {
    $menu_items = [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'url' => $base_url . $dashboard_url],
        ['icon' => 'fa-layer-group', 'label' => 'Kelas Online', 'url' => $base_url . 'modules/elearning/courses.php' . $role_param],
        ['icon' => 'fa-paper-plane', 'label' => 'Kirim Tugas', 'url' => $base_url . 'modules/elearning/student_assignments.php' . $role_param],
        ['icon' => 'fa-star', 'label' => 'Nilai Tugas', 'url' => $base_url . 'modules/elearning/student_grades.php' . $role_param],
        ['icon' => 'fa-poll', 'label' => 'Hasil Asesmen', 'url' => $base_url . 'modules/tes/hasil_ujian.php' . $role_param],
        ['icon' => 'fa-print', 'label' => 'Kartu Ujian', 'url' => $base_url . 'modules/cetak/kartu_ujian.php' . $role_param],
    ];
}
?>

<div class="container-fluid">
    <div class="d-block d-md-none mt-3 mb-3">
        <div class="card mb-3" style="background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);">
            <div class="card-body d-flex align-items-center text-white">
                <div class="me-3">
                    <?php
                    $foto = isset($_SESSION['foto']) ? $_SESSION['foto'] : '';
                    $avatar_folder = ($level === 'siswa') ? 'assets/img/siswa/' : 'assets/img/guru/';
                    $avatar_path = $avatar_folder . $foto;
                    if (!empty($foto) && file_exists($avatar_path)):
                    ?>
                        <img src="<?php echo $avatar_path; ?>" class="rounded-circle" style="width: 56px; height: 56px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background-color: rgba(255,255,255,0.2); font-size: 1.5rem;">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="small mb-1">Hai,</div>
                    <div class="fw-bold" style="font-size: 1.1rem;"><?php echo $user_name; ?></div>
                    <div class="small">Selamat datang di E-Learning</div>
                </div>
            </div>
        </div>
        <?php if (!empty($hero_image)): ?>
        <div class="mb-3 hero-kenburns-wrapper">
            <img src="<?php echo $base_url . 'assets/img/hero/' . $hero_image; ?>" class="hero-kenburns-image" alt="Hero">
        </div>
        <?php endif; ?>

        <?php if ($level === 'guru'): ?>
        <div class="mb-3">
            <div class="card shadow border-left-success py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto pr-3">
                            <div class="position-relative" style="width: 72px; height: 72px;">
                                <?php 
                                $foto_path_guru = 'assets/img/guru/' . ($_SESSION['foto'] ?? 'default.png');
                                if (empty($_SESSION['foto']) || !file_exists($foto_path_guru)) {
                                    echo '<div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white fw-bold" style="width: 72px; height: 72px; font-size: 28px;">' . strtoupper(substr($_SESSION['nama'], 0, 1)) . '</div>';
                                } else {
                                    echo '<img src="' . $foto_path_guru . '?' . time() . '" class="rounded-circle border border-white shadow-sm" style="width: 72px; height: 72px; object-fit: cover;">';
                                }
                                ?>
                                <button type="button" class="btn btn-sm btn-light rounded-circle shadow-sm position-absolute" 
                                        style="bottom: 0; right: 0; width: 28px; height: 28px; padding: 0; border: 1px solid #e3e6f0;"
                                        data-bs-toggle="modal" data-bs-target="#modalEditFotoGuru">
                                    <i class="fas fa-camera text-success"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col">
                            <div class="h6 fw-bold text-success text-uppercase mb-1">Selamat Datang, <?php echo $_SESSION['nama']; ?></div>
                            <p class="mb-0" style="font-size: 0.85rem;">Selamat datang di halaman Dashboard Guru. Anda dapat mengelola Bank Soal dan Jadwal Asesmen.</p>
                        </div>
                        <div class="col-auto d-none d-sm-block">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($menu_items)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row text-center g-3">
                    <?php foreach ($menu_items as $item): ?>
                    <div class="col-3">
                        <a href="<?php echo $item['url']; ?>" class="text-decoration-none text-dark">
                            <div class="d-flex align-items-center justify-content-center mb-1" style="width: 52px; height: 52px; margin: 0 auto; border-radius: 16px; background-color: #f1f8e9;">
                                <i class="fas <?php echo $item['icon']; ?>" style="font-size: 1.4rem; color: #2e7d32;"></i>
                            </div>
                            <div style="font-size: 0.7rem;"><?php echo $item['label']; ?></div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

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
