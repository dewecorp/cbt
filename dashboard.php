<?php
include 'config/database.php';
$page_title = 'Dashboard';
include 'includes/header.php';

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
    }
}

// Admin welcome text
$admin_welcome_text = "Aplikasi Computer Based Test (CBT) ini dirancang untuk memudahkan pelaksanaan ujian di MI Sultan Fattah Sukosono. Silahkan gunakan menu di samping untuk mengelola data dan ujian.";
$q_setting_dash = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$d_setting_dash = $q_setting_dash ? mysqli_fetch_assoc($q_setting_dash) : null;
if ($d_setting_dash && isset($d_setting_dash['admin_welcome_text']) && !empty($d_setting_dash['admin_welcome_text'])) {
    $admin_welcome_text = $d_setting_dash['admin_welcome_text'];
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
    $ujian_aktif = mysqli_query($koneksi, "
        SELECT u.*, m.nama_mapel 
        FROM ujian u 
        JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal 
        JOIN mapel m ON b.id_mapel = m.id_mapel
        WHERE u.status = 'aktif' 
        AND b.id_kelas = '$id_kelas'
        AND NOW() BETWEEN u.tgl_mulai AND u.tgl_selesai
    ");
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Ujian Aktif</div>
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
                    <h6 class="m-0 font-weight-bold text-primary">Selamat Datang di CBT MI Sultan Fattah Sukosono</h6>
                </div>
                <div class="card-body">
                    <div><?php echo $admin_welcome_text; ?></div>
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
                            <p class="mb-0">Selamat datang di halaman Dashboard Guru. Anda dapat mengelola Bank Soal dan Jadwal Ujian.</p>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
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

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Ujian Aktif Anda</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_ujian_guru; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Kartu Ujian</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <a href="modules/cetak/print_kartu.php?id_siswa=<?php echo $_SESSION['user_id']; ?>" target="_blank" class="btn btn-info btn-icon-split btn-sm">
                                    <span class="icon text-white-50">
                                        <i class="fas fa-print"></i>
                                    </span>
                                    <span class="text text-white">Cetak Kartu Ujian</span>
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

        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Ujian Aktif</h6>
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
