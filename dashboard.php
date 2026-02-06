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

// Hitung Data untuk Dashboard
$q_guru_count = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM users WHERE level='guru'");
$jml_guru = mysqli_fetch_assoc($q_guru_count)['count'];

$q_siswa_count = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM siswa WHERE status='aktif'");
$jml_siswa = mysqli_fetch_assoc($q_siswa_count)['count'];

$q_kelas_count = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM kelas");
$jml_kelas = mysqli_fetch_assoc($q_kelas_count)['count'];

$q_ujian_count = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM ujian WHERE status='aktif'");
$jml_ujian = mysqli_fetch_assoc($q_ujian_count)['count'];

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
            if ($current_level !== $d_check_u['level']) {
                $_SESSION['level'] = $d_check_u['level'];
                $level = $d_check_u['level'];
            }
        } else {
            // Tidak ketemu di users? Cek siswa
            $q_check_s = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa='$uid_check'");
            if (mysqli_num_rows($q_check_s) > 0) {
                $_SESSION['level'] = 'siswa';
                $level = 'siswa';
            }
        }

        // Logic Absensi untuk Guru (Rekap Hari Ini)
        $attendance_summary = [];
        if (!empty($clean_kelas_ids)) {
            $ids_str = implode("','", $clean_kelas_ids);
            $q_att_today = mysqli_query($koneksi, "
                SELECT a.*, s.nama_siswa, k.nama_kelas 
                FROM absensi a 
                JOIN siswa s ON a.id_siswa = s.id_siswa 
                JOIN kelas k ON a.id_kelas = k.id_kelas 
                WHERE a.tanggal = CURDATE() AND a.id_kelas IN ('$ids_str')
                ORDER BY k.nama_kelas ASC, s.nama_siswa ASC
            ");
            if($q_att_today) {
                while($row = mysqli_fetch_assoc($q_att_today)) {
                    $attendance_summary[] = $row;
                }
            }
        }
    }
}

// Admin welcome text
$admin_welcome_text = "Aplikasi Computer Based Test (CBT) ini dirancang untuk memudahkan pelaksanaan ujian di MI Sultan Fattah Sukosono. Silahkan gunakan menu di samping untuk mengelola data dan ujian.";
$q_setting_dash = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$d_setting_dash = $q_setting_dash ? mysqli_fetch_assoc($q_setting_dash) : null;
if ($d_setting_dash && isset($d_setting_dash['admin_welcome_text']) && !empty($d_setting_dash['admin_welcome_text'])) {
    $admin_welcome_text = $d_setting_dash['admin_welcome_text'];
}

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

// Data untuk guru
if($level === 'guru') {
    $id_guru = $_SESSION['user_id'];
    $q_bank_guru = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM bank_soal WHERE id_guru='$id_guru'");
    $jml_bank_soal_guru = mysqli_fetch_assoc($q_bank_guru)['count'];
    
    $q_ujian_guru = mysqli_query($koneksi, "
        SELECT COUNT(*) as count 
        FROM ujian u 
        JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal 
        WHERE b.id_guru='$id_guru' AND u.status='aktif'
    ");
    $jml_ujian_guru = mysqli_fetch_assoc($q_ujian_guru)['count'];

    $q_course_guru = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM courses WHERE pengampu='$id_guru'");
    $jml_course_guru = mysqli_fetch_assoc($q_course_guru)['count'];

    // Data Siswa per Kelas yang diajar
    $teacher_classes = [];
    $jml_mapel_guru = 0;
    
    $q_guru = mysqli_query($koneksi, "SELECT mengajar_kelas, mengajar_mapel FROM users WHERE id_user='$id_guru'");
    $d_guru = mysqli_fetch_assoc($q_guru);
    
    if ($d_guru) {
        // Count Mapel
        if (!empty($d_guru['mengajar_mapel'])) {
            $mapel_ids = explode(',', $d_guru['mengajar_mapel']);
            $clean_mapel_ids = array_filter($mapel_ids, function($value) { return !empty(trim($value)); });
            $jml_mapel_guru = count($clean_mapel_ids);
        }

        // Process Classes Optimized
        if (!empty($d_guru['mengajar_kelas'])) {
            $kelas_ids_raw = explode(',', $d_guru['mengajar_kelas']);
            $clean_kelas_ids = [];
            foreach($kelas_ids_raw as $kid) {
                $kid = trim($kid);
                if(!empty($kid)) $clean_kelas_ids[] = mysqli_real_escape_string($koneksi, $kid);
            }

            if (!empty($clean_kelas_ids)) {
                $ids_str = implode("','", $clean_kelas_ids);
                $query_classes = "
                    SELECT k.nama_kelas, COUNT(s.id_siswa) as count 
                    FROM kelas k 
                    LEFT JOIN siswa s ON k.id_kelas = s.id_kelas AND s.status='aktif' 
                    WHERE k.id_kelas IN ('$ids_str') 
                    GROUP BY k.id_kelas, k.nama_kelas
                ";
                $q_classes = mysqli_query($koneksi, $query_classes);
                
                while($row = mysqli_fetch_assoc($q_classes)) {
                    $teacher_classes[] = [
                        'nama_kelas' => $row['nama_kelas'],
                        'jumlah_siswa' => $row['count']
                    ];
                }
            }
        }
    }
}

// Data untuk siswa
if($level === 'siswa') {
    $id_kelas = $_SESSION['id_kelas'];
    
    // Logic Absensi Siswa (Dashboard - General Attendance)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance_dash'])) {
        $status = mysqli_real_escape_string($koneksi, $_POST['status']);
        $keterangan = isset($_POST['keterangan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan']) : '';
        $today = date('Y-m-d');
        $time = date('H:i:s');
        $uid = $_SESSION['user_id'];
        
        // Check if already attended today (general attendance has id_course = 0 or NULL)
        // We use id_course = 0 for dashboard/school attendance
        $check = mysqli_query($koneksi, "SELECT id_absensi FROM absensi WHERE id_siswa='$uid' AND (id_course='0' OR id_course IS NULL) AND tanggal='$today'");
        if (mysqli_num_rows($check) == 0) {
            $insert = mysqli_query($koneksi, "INSERT INTO absensi (id_siswa, id_kelas, id_course, tanggal, jam_masuk, status, keterangan) VALUES ('$uid', '$id_kelas', '0', '$today', '$time', '$status', '$keterangan')");
            if ($insert) {
                 echo "<script>
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Absensi harian berhasil disimpan.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                 </script>";
            }
        }
    }

    $ujian_aktif = mysqli_query($koneksi, "
        SELECT u.*, m.nama_mapel 
        FROM ujian u 
        JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal 
        JOIN mapel m ON b.id_mapel = m.id_mapel
        WHERE u.status = 'aktif' 
        AND b.id_kelas = '$id_kelas'
        AND NOW() BETWEEN u.tgl_mulai AND u.tgl_selesai
    ");
    
    // Announcements for siswa
    $ann_siswa = mysqli_query($koneksi, "
        SELECT a.*, c.nama_course, u.nama_lengkap 
        FROM announcements a 
        LEFT JOIN courses c ON a.course_id = c.id_course 
        JOIN users u ON a.created_by = u.id_user 
        WHERE (a.course_id IS NULL OR c.id_kelas = '$id_kelas')
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    
    // Fetch courses with active assignments (tasks)
    $tugas_courses = [];
    $uid_siswa = $_SESSION['user_id'];
    $q_tugas_active = mysqli_query($koneksi, "
        SELECT c.id_course, c.nama_course, m.nama_mapel, u.nama_lengkap as nama_guru,
               COUNT(a.id_assignment) as total_active,
               SUM(CASE WHEN s.id_submission IS NOT NULL THEN 1 ELSE 0 END) as submitted_count
        FROM assignments a
        JOIN courses c ON a.course_id = c.id_course
        JOIN mapel m ON c.id_mapel = m.id_mapel
        JOIN users u ON c.pengampu = u.id_user
        LEFT JOIN submissions s ON a.id_assignment = s.assignment_id AND s.siswa_id = '$uid_siswa'
        WHERE c.id_kelas = '$id_kelas'
        AND a.deadline >= NOW()
        GROUP BY c.id_course
        ORDER BY c.created_at DESC
    ");
    if($q_tugas_active) {
        while($row = mysqli_fetch_assoc($q_tugas_active)) {
            $tugas_courses[] = $row;
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

    <?php if($level === 'admin'): ?>
    <div class="row">
        <!-- Data Guru Widget -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Data Guru</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_guru; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Data Siswa</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_siswa; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 border-start border-4 border-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Data Kelas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_kelas; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-school fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Asesmen Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_ujian; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Selamat Datang di E-Learning MI Sultan Fattah Sukosono</h6>
                </div>
                <div class="card-body">
                    <div><?php echo $admin_welcome_text; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Aktivitas Pengguna (24 Jam)</h6>
                    <div>
                        <?php
                        $log_count_24h = 0;
                        $rs_cnt = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM activity_log WHERE created_at >= (NOW() - INTERVAL 1 DAY)");
                        if ($rs_cnt) { $row_cnt = mysqli_fetch_assoc($rs_cnt); $log_count_24h = (int)$row_cnt['c']; }
                        ?>
                        <span class="badge bg-primary rounded-pill"><?php echo $log_count_24h; ?> Aktivitas</span>
                    </div>
                </div>
                <div class="card-body" style="max-height: 420px; overflow-y: auto;">
                    <?php
                    $logs = mysqli_query($koneksi, "
                        SELECT l.*, 
                               u.nama_lengkap as nama_guru,
                               s.nama_siswa as nama_siswa
                        FROM activity_log l
                        LEFT JOIN users u ON l.user_id = u.id_user AND l.level IN ('admin', 'guru')
                        LEFT JOIN siswa s ON l.user_id = s.id_siswa AND l.level = 'siswa'
                        ORDER BY l.created_at DESC 
                        LIMIT 100
                    ");
                    ?>
                    <style>
                        .timeline { position: relative; padding-left: 1.5rem; }
                        .timeline::before { content: ''; position: absolute; left: 0.6rem; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
                        .timeline-item { position: relative; margin-bottom: 1rem; padding-left: 1rem; }
                        .timeline-item::before { content: ''; position: absolute; left: -0.1rem; top: 0.9rem; width: 10px; height: 10px; background: #4e73df; border-radius: 50%; box-shadow: 0 0 0 3px rgba(78,115,223,0.15); }
                        .timeline-meta { font-size: 0.85rem; color: #6c757d; }
                        .timeline-title { font-weight: 600; }
                        .timeline-badge { display: inline-flex; align-items: center; gap: 0.35rem; }
                        .timeline-badge i { color: #4e73df; }
                        .timeline-card { background: #fff; border: 1px solid #e9ecef; border-radius: 0.5rem; padding: 0.75rem 0.9rem; box-shadow: 0 2px 6px rgba(0,0,0,0.05); margin-left: 0.5rem; }
                        .timeline-card .timeline-title { letter-spacing: 0.02em; }
                        .timeline-card.t-login { border-left: 4px solid #0d6efd; }
                        .timeline-card.t-logout { border-left: 4px solid #dc3545; }
                        .timeline-card.t-create { border-left: 4px solid #198754; }
                        .timeline-card.t-update { border-left: 4px solid #ffc107; }
                        .timeline-card.t-delete { border-left: 4px solid #dc3545; }
                        .timeline-card.t-import { border-left: 4px solid #0dcaf0; }
                        .timeline-content { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
                    </style>
                    <div class="timeline">
                        <?php while($lg = mysqli_fetch_assoc($logs)): ?>
                            <?php
                                $ico = 'fa-circle';
                                if ($lg['action'] === 'login') $ico = 'fa-sign-in-alt';
                                elseif ($lg['action'] === 'logout') $ico = 'fa-sign-out-alt';
                                elseif ($lg['action'] === 'create') $ico = 'fa-plus-circle';
                                elseif ($lg['action'] === 'update') $ico = 'fa-edit';
                                elseif ($lg['action'] === 'delete') $ico = 'fa-trash';
                                elseif ($lg['action'] === 'import') $ico = 'fa-file-import';
                                
                                // Determine display name
                                $display_name = $lg['username']; // Fallback
                                if ($lg['level'] === 'siswa' && !empty($lg['nama_siswa'])) {
                                    $display_name = $lg['nama_siswa'];
                                } elseif (($lg['level'] === 'guru' || $lg['level'] === 'admin') && !empty($lg['nama_guru'])) {
                                    $display_name = $lg['nama_guru'];
                                }
                                
                                $who = trim(($display_name ?? '') . ' ' . ($lg['level'] ? '(' . $lg['level'] . ')' : ''));
                                $dt = date('d/m/Y H:i:s', strtotime($lg['created_at'])) . ' • ' . time_ago_str($lg['created_at']);
                                $act_class = 't-' . strtolower($lg['action']);
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-card <?php echo $act_class; ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="timeline-badge">
                                            <i class="fas <?php echo $ico; ?>"></i>
                                            <span class="timeline-title"><?php echo strtoupper($lg['action']); ?></span>
                                        </div>
                                    </div>
                                    <div class="timeline-content mb-1">
                                        <span class="badge bg-secondary"><?php echo $lg['module']; ?></span>
                                        <?php if(!empty($who)): ?><span class="badge bg-light text-dark"><?php echo $who; ?></span><?php endif; ?>
                                        <?php if(!empty($lg['details'])): ?><span class="text-muted"><?php echo htmlspecialchars($lg['details']); ?></span><?php endif; ?>
                                    </div>
                                    <div class="timeline-meta"><?php echo $dt; ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($level === 'guru'): ?>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow border-left-primary py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="h5 font-weight-bold text-primary text-uppercase mb-1">Selamat Datang, <?php echo $_SESSION['nama']; ?></div>
                            <p class="mb-0">Selamat datang di halaman Dashboard Guru. Anda dapat mengelola Bank Soal dan Jadwal Asesmen.</p>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 border-start border-4 border-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bank Soal Anda</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_bank_soal_guru; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Asesmen Aktif Anda</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_ujian_guru; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kelas Online</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_course_guru; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Mapel Diampu</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_mapel_guru; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if(!empty($teacher_classes)): ?>
            <?php foreach($teacher_classes as $tc): ?>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Siswa Kelas <?php echo $tc['nama_kelas']; ?></div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $tc['jumlah_siswa']; ?> Siswa</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Rekap Absensi Hari Ini -->
        <div class="col-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-check me-2"></i>Rekap Absensi Siswa Hari Ini (<?php echo date('d/m/Y'); ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if(!empty($attendance_summary)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTableAbsensi" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($attendance_summary as $as): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($as['nama_siswa']); ?></td>
                                    <td><?php echo htmlspecialchars($as['nama_kelas']); ?></td>
                                    <td>
                                        <?php 
                                        $badge = 'secondary';
                                        if($as['status'] == 'Hadir') $badge = 'success';
                                        elseif($as['status'] == 'Sakit') $badge = 'warning';
                                        elseif($as['status'] == 'Izin') $badge = 'info';
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo $as['status']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($as['keterangan'] ?? '-'); ?></td>
                                    <td><?php echo date('H:i', strtotime($as['waktu_absen'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info text-center">
                        Belum ada data absensi siswa hari ini untuk kelas yang Anda ampu.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($level === 'siswa'): ?>
    <div class="row">
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow border-left-primary py-2 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="h5 font-weight-bold text-primary text-uppercase mb-1">Selamat Datang, <?php echo $_SESSION['nama']; ?></div>
                            <p class="mb-0">Silahkan cek daftar ujian yang tersedia di bawah ini.</p>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-smile fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card border-left-info shadow h-100 py-2 border-start border-4 border-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Kartu Asesmen</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <a href="modules/cetak/print_kartu.php?id_siswa=<?php echo $_SESSION['user_id']; ?>" target="_blank" class="btn btn-info btn-icon-split btn-sm">
                                    <span class="icon text-white-50">
                                        <i class="fas fa-print"></i>
                                    </span>
                                    <span class="text text-white">Cetak Kartu Asesmen</span>
                                </a>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-id-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Absensi Widget -->
        <?php
        $attendance_today_dash = null;
        $today = date('Y-m-d');
        $uid = $_SESSION['user_id'];
        $qa_dash = mysqli_query($koneksi, "SELECT * FROM absensi WHERE id_siswa='$uid' AND (id_course='0' OR id_course IS NULL) AND tanggal='$today'");
        if ($qa_dash && mysqli_num_rows($qa_dash) > 0) {
            $attendance_today_dash = mysqli_fetch_assoc($qa_dash);
        }
        ?>
        <div class="col-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Absensi Hari Ini</h6>
                </div>
                <div class="card-body text-center">
                    <p class="mb-4"><?php echo get_indo_day($today) . ', ' . date('d F Y'); ?></p>
                    
                    <?php if ($attendance_today_dash): ?>
                        <div class="alert alert-success">
                            Anda sudah melakukan absensi harian.<br>
                            <strong>Status: <?php echo $attendance_today_dash['status']; ?></strong>
                            <?php if ($attendance_today_dash['status'] != 'Hadir' && !empty($attendance_today_dash['keterangan'])): ?>
                                <br>Keterangan: <?php echo htmlspecialchars($attendance_today_dash['keterangan']); ?>
                            <?php endif; ?>
                            <br>Jam: <?php echo $attendance_today_dash['jam_masuk']; ?>
                        </div>
                    <?php else: ?>
                        <div class="d-grid gap-2 d-md-block">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="submit_attendance_dash" value="1">
                                <input type="hidden" name="status" value="Hadir">
                                <button type="submit" class="btn btn-success btn-lg px-5 mx-2 mb-2">
                                    <i class="fas fa-check-circle me-2"></i> HADIR
                                </button>
                            </form>
                            
                            <button type="button" class="btn btn-warning btn-lg px-5 mx-2 mb-2" data-bs-toggle="modal" data-bs-target="#modalSakitDash">
                                <i class="fas fa-procedures me-2"></i> SAKIT
                            </button>
                            
                            <button type="button" class="btn btn-info btn-lg px-5 mx-2 mb-2" data-bs-toggle="modal" data-bs-target="#modalIzinDash">
                                <i class="fas fa-envelope-open-text me-2"></i> IZIN
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modals for Sakit/Izin Dashboard -->
        <?php if (!$attendance_today_dash): ?>
        <!-- Modal Sakit -->
        <div class="modal fade" id="modalSakitDash" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Konfirmasi Sakit (Harian)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="submit_attendance_dash" value="1">
                            <input type="hidden" name="status" value="Sakit">
                            <div class="mb-3">
                                <label class="form-label">Keterangan / Upload Surat Dokter (Opsional)</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Tulis keterangan sakit..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">Kirim Absensi Sakit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Izin -->
        <div class="modal fade" id="modalIzinDash" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Konfirmasi Izin (Harian)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="submit_attendance_dash" value="1">
                            <input type="hidden" name="status" value="Izin">
                            <div class="mb-3">
                                <label class="form-label">Alasan Izin</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Tulis alasan izin..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-info">Kirim Absensi Izin</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pengumuman Widget -->
        <?php if(mysqli_num_rows($ann_siswa) > 0): ?>
        <div class="col-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bullhorn me-2"></i>Pengumuman Terbaru</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php while($ann = mysqli_fetch_assoc($ann_siswa)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="alert alert-info border-left-info shadow-sm h-100">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="alert-heading font-weight-bold mb-1"><i class="fas fa-info-circle me-1"></i> <?php echo htmlspecialchars($ann['title'] ?? ''); ?></h5>
                                    <small class="text-muted text-nowrap ms-2"><?php echo time_ago_str($ann['created_at']); ?></small>
                                </div>
                                <hr class="my-2">
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($ann['body'] ?? '')); ?></p>
                                <div class="small text-muted mt-2 d-flex justify-content-between">
                                    <span><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($ann['nama_lengkap']); ?></span>
                                    <?php if($ann['nama_course']): ?>
                                        <span><i class="fas fa-chalkboard me-1"></i> <?php echo htmlspecialchars($ann['nama_course']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tugas Widget -->
        <?php if(!empty($tugas_courses)): ?>
        <div class="col-12 mb-3">
            <h5 class="font-weight-bold text-gray-800"><i class="fas fa-tasks me-2"></i>Daftar Tugas</h5>
        </div>
        <?php foreach($tugas_courses as $tc): 
            $jml_belum = $tc['total_active'] - $tc['submitted_count'];
            $border_class = $jml_belum > 0 ? 'border-left-danger border-danger' : 'border-left-success border-success';
            $text_class = $jml_belum > 0 ? 'text-danger' : 'text-success';
            $icon_class = $jml_belum > 0 ? 'fa-exclamation-circle' : 'fa-check-circle';
        ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="modules/elearning/course_manage.php?course_id=<?php echo $tc['id_course']; ?>&tab=tugas" class="text-decoration-none">
                <div class="card <?php echo $border_class; ?> shadow h-100 py-2 border-start border-4">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold <?php echo $text_class; ?> text-uppercase mb-1">
                                    <?php echo htmlspecialchars($tc['nama_mapel']); ?>
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800 text-truncate" style="max-width: 150px;">
                                    <?php echo htmlspecialchars($tc['nama_course']); ?>
                                </div>
                                <div class="mt-2 small <?php echo $text_class; ?> fw-bold">
                                    <i class="fas <?php echo $icon_class; ?>"></i> 
                                    <?php if($jml_belum > 0): ?>
                                        <?php echo $jml_belum; ?> Tugas Belum Selesai
                                    <?php else: ?>
                                        Semua Tugas Selesai
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Asesmen Aktif</h6>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($ujian_aktif) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Nama Ujian</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($u = mysqli_fetch_assoc($ujian_aktif)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $u['nama_mapel']; ?></td>
                                    <td><?php echo $u['nama_ujian']; ?></td>
                                    <td><?php echo $u['waktu']; ?> Menit</td>
                                    <td><span class="badge bg-success">Aktif</span></td>
                                    <td>
                                        <a href="modules/tes/konfirmasi.php?id=<?php echo $u['id_ujian']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Kerjakan
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        Tidak ada ujian yang aktif saat ini.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
