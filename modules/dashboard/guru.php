<?php
// Logic for Guru Dashboard
// Handle Photo Upload
if (isset($_POST['upload_foto_guru'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === 0) {
        $file_type = $_FILES['foto_profil']['type'];
        $file_size = $_FILES['foto_profil']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            echo "<script>Swal.fire('Gagal', 'Format file harus JPG, PNG, atau GIF', 'error');</script>";
        } elseif ($file_size > $max_size) {
            echo "<script>Swal.fire('Gagal', 'Ukuran file maksimal 2MB', 'error');</script>";
        } else {
            $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
            $new_name = $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $target_dir = "assets/img/guru/";
            
            // Create dir if not exists
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $target_file = $target_dir . $new_name;
            
            // Delete old photo
            $old_photo = isset($_SESSION['foto']) ? $_SESSION['foto'] : '';
            if (!empty($old_photo) && file_exists($target_dir . $old_photo) && $old_photo != 'default.png') {
                unlink($target_dir . $old_photo);
            }
            
            if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                // Update DB
                $uid = $_SESSION['user_id'];
                mysqli_query($koneksi, "UPDATE users SET foto='$new_name' WHERE id_user='$uid'");
                
                // Update Session
                $_SESSION['foto'] = $new_name;
                
                echo "<script>
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Foto profil berhasil diperbarui.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'dashboard.php?role=guru';
                    });
                </script>";
            } else {
                echo "<script>Swal.fire('Gagal', 'Gagal mengupload file', 'error');</script>";
            }
        }
    }
}

$id_guru = $_SESSION['user_id'];
$q_bank_guru = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM bank_soal WHERE id_guru='$id_guru'");
$jml_bank_soal_guru = mysqli_fetch_assoc($q_bank_guru)['count'];

$q_ujian_guru = mysqli_query($koneksi, "
    SELECT COUNT(*) as count 
    FROM ujian u 
    JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal 
    WHERE b.id_guru='$id_guru' AND u.status='aktif'
    AND NOW() BETWEEN u.tgl_mulai AND u.tgl_selesai
");
$jml_ujian_guru = mysqli_fetch_assoc($q_ujian_guru)['count'];

$q_course_guru = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM courses WHERE pengampu='$id_guru'");
$jml_course_guru = mysqli_fetch_assoc($q_course_guru)['count'];

// Tambahan Statistik Siswa (Tugas & CBT) untuk Guru
// Siswa mengerjakan tugas di kelas yang diampu guru ini
$q_siswa_tugas_guru = mysqli_query($koneksi, "
    SELECT COUNT(DISTINCT s.siswa_id) as count 
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id_assignment
    JOIN courses c ON a.course_id = c.id_course
    WHERE c.pengampu = '$id_guru'
");
$jml_siswa_tugas_guru = mysqli_fetch_assoc($q_siswa_tugas_guru)['count'];

// Siswa mengerjakan CBT di bank soal yang dibuat guru ini
$q_siswa_cbt_guru = mysqli_query($koneksi, "
    SELECT COUNT(DISTINCT us.id_siswa) as count 
    FROM ujian_siswa us
    JOIN ujian u ON us.id_ujian = u.id_ujian
    JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal
    WHERE b.id_guru = '$id_guru'
");
$jml_siswa_cbt_guru = mysqli_fetch_assoc($q_siswa_cbt_guru)['count'];

// Total siswa unik di semua kelas yang diampu guru
$total_siswa_diampu = 0;
$q_guru_info = mysqli_query($koneksi, "SELECT mengajar_kelas FROM users WHERE id_user='$id_guru'");
$d_guru_info = mysqli_fetch_assoc($q_guru_info);
if ($d_guru_info && !empty($d_guru_info['mengajar_kelas'])) {
    $kelas_ids = explode(',', $d_guru_info['mengajar_kelas']);
    $clean_kelas_ids = array_filter(array_map('trim', $kelas_ids));
    if (!empty($clean_kelas_ids)) {
        $ids_str = implode("','", array_map(function($id) use ($koneksi) { return mysqli_real_escape_string($koneksi, $id); }, $clean_kelas_ids));
        $q_total_siswa = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM siswa WHERE id_kelas IN ('$ids_str') AND status='aktif'");
        $total_siswa_diampu = mysqli_fetch_assoc($q_total_siswa)['count'];
    }
}
$jml_siswa_belum_tugas_guru = max(0, $total_siswa_diampu - $jml_siswa_tugas_guru);
$jml_siswa_belum_cbt_guru = max(0, $total_siswa_diampu - $jml_siswa_cbt_guru);

// Statistik Tambahan Guru (Materi, Tugas, Pengumuman)
$q_guru_materi = mysqli_query($koneksi, "
    SELECT COUNT(*) as count 
    FROM materials m
    JOIN courses c ON m.course_id = c.id_course
    WHERE c.pengampu = '$id_guru'
");
$jml_materi_guru = mysqli_fetch_assoc($q_guru_materi)['count'];

$q_guru_tugas = mysqli_query($koneksi, "
    SELECT COUNT(*) as count 
    FROM assignments a
    JOIN courses c ON a.course_id = c.id_course
    WHERE c.pengampu = '$id_guru'
");
$jml_tugas_guru = mysqli_fetch_assoc($q_guru_tugas)['count'];

$q_guru_ann = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM announcements WHERE created_by = '$id_guru'");
$jml_ann_guru = mysqli_fetch_assoc($q_guru_ann)['count'];

// Data Siswa per Kelas yang diajar
$teacher_classes = [];
$attendance_summary = [];
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
                SELECT k.id_kelas, k.nama_kelas, COUNT(s.id_siswa) as count 
                FROM kelas k 
                LEFT JOIN siswa s ON k.id_kelas = s.id_kelas AND s.status='aktif' 
                WHERE k.id_kelas IN ('$ids_str') 
                GROUP BY k.id_kelas, k.nama_kelas
            ";
            $q_classes = mysqli_query($koneksi, $query_classes);
            
            while($row = mysqli_fetch_assoc($q_classes)) {
                $teacher_classes[] = [
                    'id_kelas' => $row['id_kelas'],
                    'nama_kelas' => $row['nama_kelas'],
                    'jumlah_siswa' => $row['count']
                ];
            }

            // Logic Absensi untuk Guru (Rekap Hari Ini)
            $attendance_summary = [];
            $ids_str = implode("','", $clean_kelas_ids);
            $q_att_today = mysqli_query($koneksi, "
                SELECT a.*, s.nama_siswa, k.nama_kelas, c.nama_course, m.nama_mapel
                FROM absensi a 
                JOIN siswa s ON a.id_siswa = s.id_siswa 
                JOIN kelas k ON a.id_kelas = k.id_kelas
                LEFT JOIN courses c ON a.id_course = c.id_course
                LEFT JOIN mapel m ON c.id_mapel = m.id_mapel
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
?>

<div class="row">
    <div class="col-xl-9 col-lg-8 mb-4 d-none d-md-block">
        <div class="card shadow border-left-success py-2 h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto pr-3">
                        <div class="position-relative" style="width: 80px; height: 80px;">
                            <?php 
                            $foto_path = 'assets/img/guru/' . ($_SESSION['foto'] ?? 'default.png');
                            if (empty($_SESSION['foto']) || !file_exists($foto_path)) {
                                // Fallback UI if no photo
                                echo '<div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 80px; height: 80px; font-size: 32px;">' . strtoupper(substr($_SESSION['nama'], 0, 1)) . '</div>';
                            } else {
                                echo '<img src="' . $foto_path . '?' . time() . '" class="rounded-circle border border-white shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">';
                            }
                            ?>
                            <button type="button" class="btn btn-sm btn-light rounded-circle shadow-sm position-absolute" 
                                    style="bottom: 0; right: 0; width: 30px; height: 30px; padding: 0; border: 1px solid #e3e6f0;"
                                    data-bs-toggle="modal" data-bs-target="#modalEditFotoGuru">
                                <i class="fas fa-camera text-success"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col">
                        <div class="h5 font-weight-bold text-success text-uppercase mb-1">Selamat Datang, <?php echo $_SESSION['nama']; ?></div>
                        <p class="mb-0">Selamat datang di halaman Dashboard Guru. Anda dapat mengelola Bank Soal dan Jadwal Asesmen.</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Total Siswa Diampu -->
    <div class="col-xl-3 col-lg-4 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 border-start border-4 border-primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Siswa Anda</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_siswa_diampu; ?> Siswa</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">

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
        <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Mapel Diampu</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_mapel_guru; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Materi, Tugas, Pengumuman Guru -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 border-start border-4 border-primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Materi Anda</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_materi_guru; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book-open fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tugas Anda</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_tugas_guru; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2 border-start border-4 border-secondary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Pengumuman Anda</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_ann_guru; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Siswa (Tugas & CBT) -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Siswa Sudah Tugas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_siswa_tugas_guru; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2 border-start border-4 border-danger">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Siswa Belum Tugas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_siswa_belum_tugas_guru; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-times fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Siswa Sudah CBT</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_siswa_cbt_guru; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-edit fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 border-start border-4 border-warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Siswa Belum CBT</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $jml_siswa_belum_cbt_guru; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Rekap Absensi Hari Ini -->
    <div class="col-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-calendar-check me-2"></i>Rekap Absensi Siswa Hari Ini (<?php echo date('d/m/Y'); ?>)</h6>
                <a href="modules/elearning/rekap_absensi.php?role=guru" class="btn btn-sm btn-success">
                    <i class="fas fa-list-alt me-1"></i> Lihat Rekap Bulanan
                </a>
            </div>
            <div class="card-body">
                <?php if(!empty($attendance_summary)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTableAbsensi" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>Mata Pelajaran</th>
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
                                <td>
                                    <?php 
                                        if (!empty($as['nama_mapel'])) {
                                            echo htmlspecialchars($as['nama_mapel']);
                                        } else {
                                            echo '<span class="text-muted fst-italic">Absensi Harian</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $badge = 'secondary';
                                    if($as['status'] == 'Hadir') $badge = 'success';
                                    elseif($as['status'] == 'Sakit') $badge = 'warning';
                                    elseif($as['status'] == 'Izin') $badge = 'info';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo $as['status']; ?></span>
                                </td>
                                <td><?php echo $as['keterangan'] ?? '-'; ?></td>
                                <td><?php echo date('H:i', strtotime($as['waktu_absen'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="card bg-info bg-opacity-10 border border-info text-info p-3 rounded text-center">
                    <i class="fas fa-info-circle me-1"></i> Belum ada data absensi siswa hari ini untuk kelas yang Anda ampu.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Modal Edit Foto Guru -->
<div class="modal fade" id="modalEditFotoGuru" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Perbarui Foto Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <img id="previewFoto" src="<?php echo !empty($_SESSION['foto']) && file_exists('assets/img/guru/'.$_SESSION['foto']) ? 'assets/img/guru/'.$_SESSION['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['nama']).'&size=150&background=random'; ?>" 
                                class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <p class="text-muted small">Format: JPG, PNG, GIF. Maks: 2MB.</p>
                    </div>
                    <div class="mb-3">
                        <input class="form-control" type="file" name="foto_profil" accept="image/*" onchange="previewImage(this)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="upload_foto_guru" class="btn btn-primary">Simpan Foto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewFoto').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
