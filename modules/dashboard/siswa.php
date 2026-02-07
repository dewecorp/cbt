<?php
$id_kelas = $_SESSION['id_kelas'];

// Handle Upload Foto Siswa
if (isset($_POST['upload_foto_siswa'])) {
    $target_dir = "assets/img/siswa/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["foto_profil"]["name"], PATHINFO_EXTENSION);
    $new_name = "siswa_" . $_SESSION['user_id'] . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_name;
    
    $uploadOk = 1;
    $imageFileType = strtolower($file_extension);

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["foto_profil"]["tmp_name"]);
    if($check === false) {
        echo "<script>Swal.fire('Gagal', 'File bukan gambar.', 'error');</script>";
        $uploadOk = 0;
    }

    // Check file size (2MB)
    if ($_FILES["foto_profil"]["size"] > 2000000) {
        echo "<script>Swal.fire('Gagal', 'Ukuran file terlalu besar (Maks 2MB).', 'error');</script>";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        echo "<script>Swal.fire('Gagal', 'Hanya format JPG, JPEG, PNG & GIF yang diperbolehkan.', 'error');</script>";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
            // Update DB
            $uid = $_SESSION['user_id'];
            mysqli_query($koneksi, "UPDATE siswa SET foto='$new_name' WHERE id_siswa='$uid'");
            
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
                    window.location.href = 'dashboard.php';
                });
            </script>";
        } else {
            echo "<script>Swal.fire('Gagal', 'Gagal mengupload file', 'error');</script>";
        }
    }
}

// Logic Absensi Siswa (Dashboard - General Attendance + Scheduled Courses)
// SELF-HEALING: Sync General Attendance to Course Attendance automatically
$today_sync = date('Y-m-d');
$uid_sync = $_SESSION['user_id'];
$check_general = mysqli_query($koneksi, "SELECT * FROM absensi WHERE id_siswa='$uid_sync' AND (id_course='0' OR id_course IS NULL) AND tanggal='$today_sync' LIMIT 1");

if (mysqli_num_rows($check_general) > 0) {
    $general_att = mysqli_fetch_assoc($check_general);
    $hari_indo_sync = get_indo_day(date('l'));
    
    $q_jadwal_sync = mysqli_query($koneksi, "SELECT mapel_ids FROM jadwal_pelajaran WHERE id_kelas='$id_kelas' AND hari='$hari_indo_sync'");
    if ($q_jadwal_sync && mysqli_num_rows($q_jadwal_sync) > 0) {
        $row_jadwal_sync = mysqli_fetch_assoc($q_jadwal_sync);
        if (!empty($row_jadwal_sync['mapel_ids'])) {
            $mapel_ids_sync = explode(',', $row_jadwal_sync['mapel_ids']);
            foreach ($mapel_ids_sync as $mid_sync) {
                $mid_sync = trim($mid_sync);
                if (empty($mid_sync)) continue;
                
                // Find course
                $q_course_sync = mysqli_query($koneksi, "SELECT id_course FROM courses WHERE id_kelas='$id_kelas' AND id_mapel='$mid_sync'");
                if ($r_course_sync = mysqli_fetch_assoc($q_course_sync)) {
                    $cid_sync = $r_course_sync['id_course'];
                    
                    // Check if exists
                    $check_c_sync = mysqli_query($koneksi, "SELECT id_absensi FROM absensi WHERE id_siswa='$uid_sync' AND id_course='$cid_sync' AND tanggal='$today_sync'");
                    if (mysqli_num_rows($check_c_sync) == 0) {
                        // Backfill
                        $s_stat = $general_att['status'];
                        $s_ket = mysqli_real_escape_string($koneksi, $general_att['keterangan']);
                        $s_time = $general_att['jam_masuk'];
                        
                        mysqli_query($koneksi, "INSERT INTO absensi (id_siswa, id_kelas, id_course, tanggal, jam_masuk, status, keterangan) VALUES ('$uid_sync', '$id_kelas', '$cid_sync', '$today_sync', '$s_time', '$s_stat', '$s_ket')");
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance_dash'])) {
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $keterangan = isset($_POST['keterangan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan']) : '';
    $today = date('Y-m-d');
    $time = date('H:i:s');
    $uid = $_SESSION['user_id'];
    
    // 1. Insert General Attendance (id_course = 0)
    $check = mysqli_query($koneksi, "SELECT id_absensi FROM absensi WHERE id_siswa='$uid' AND (id_course='0' OR id_course IS NULL) AND tanggal='$today'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($koneksi, "INSERT INTO absensi (id_siswa, id_kelas, id_course, tanggal, jam_masuk, status, keterangan) VALUES ('$uid', '$id_kelas', '0', '$today', '$time', '$status', '$keterangan')");
    }
    
    // 2. Insert Course-Specific Attendance based on Schedule
    $hari_indo = get_indo_day(date('l')); // Using existing function
    
    $q_jadwal = mysqli_query($koneksi, "SELECT mapel_ids FROM jadwal_pelajaran WHERE id_kelas='$id_kelas' AND hari='$hari_indo'");
    if (mysqli_num_rows($q_jadwal) > 0) {
        $row_jadwal = mysqli_fetch_assoc($q_jadwal);
        if (!empty($row_jadwal['mapel_ids'])) {
            $mapel_ids = explode(',', $row_jadwal['mapel_ids']);
            foreach ($mapel_ids as $mid) {
                $mid = trim($mid);
                if (empty($mid)) continue;
                
                // Find course for this mapel & class
                $q_course = mysqli_query($koneksi, "SELECT id_course FROM courses WHERE id_kelas='$id_kelas' AND id_mapel='$mid'");
                if ($r_course = mysqli_fetch_assoc($q_course)) {
                    $cid = $r_course['id_course'];
                    // Check duplication
                    $check_c = mysqli_query($koneksi, "SELECT id_absensi FROM absensi WHERE id_siswa='$uid' AND id_course='$cid' AND tanggal='$today'");
                    if (mysqli_num_rows($check_c) == 0) {
                            mysqli_query($koneksi, "INSERT INTO absensi (id_siswa, id_kelas, id_course, tanggal, jam_masuk, status, keterangan) VALUES ('$uid', '$id_kelas', '$cid', '$today', '$time', '$status', '$keterangan')");
                    }
                }
            }
        }
    }

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
?>

<div class="row">
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow border-left-success py-2 h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="position-relative">
                            <img src="<?php echo !empty($_SESSION['foto']) && file_exists('assets/img/siswa/'.$_SESSION['foto']) ? 'assets/img/siswa/'.$_SESSION['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['nama']).'&size=100&background=random'; ?>" 
                                 class="img-profile rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                            <button class="btn btn-sm btn-light position-absolute bottom-0 end-0 rounded-circle border shadow-sm p-1" 
                                    style="width: 30px; height: 30px;"
                                    data-bs-toggle="modal" data-bs-target="#modalEditFotoSiswa">
                                <i class="fas fa-camera text-secondary small"></i>
                            </button>
                        </div>
<!-- Modal Edit Foto Siswa -->
<div class="modal fade" id="modalEditFotoSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Perbarui Foto Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <img id="previewFotoSiswa" src="<?php echo !empty($_SESSION['foto']) && file_exists('assets/img/siswa/'.$_SESSION['foto']) ? 'assets/img/siswa/'.$_SESSION['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['nama']).'&size=150&background=random'; ?>" 
                                class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <p class="text-muted small">Format: JPG, PNG, GIF. Maks: 2MB.</p>
                    </div>
                    <div class="mb-3">
                        <input class="form-control" type="file" name="foto_profil" accept="image/*" onchange="previewImageSiswa(this)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="upload_foto_siswa" class="btn btn-primary">Simpan Foto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImageSiswa(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewFotoSiswa').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
                    </div>
                    <div class="col mr-2">
                        <div class="h5 font-weight-bold text-success text-uppercase mb-1">Selamat Datang, <?php echo $_SESSION['nama']; ?></div>
                        <p class="mb-0">Silahkan cek daftar ujian yang tersedia di bawah ini.</p>
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
    
    // 1. Check Attendance Record
    $qa_dash = mysqli_query($koneksi, "SELECT * FROM absensi WHERE id_siswa='$uid' AND (id_course='0' OR id_course IS NULL) AND tanggal='$today'");
    if ($qa_dash && mysqli_num_rows($qa_dash) > 0) {
        $attendance_today_dash = mysqli_fetch_assoc($qa_dash);
    }

    // 2. Check Schedule / Holiday
    $hari_ini_indo = get_indo_day($today);
    $has_schedule = false;
    $q_cek_jadwal = mysqli_query($koneksi, "SELECT mapel_ids FROM jadwal_pelajaran WHERE id_kelas='$id_kelas' AND hari='$hari_ini_indo'");
    if ($q_cek_jadwal && mysqli_num_rows($q_cek_jadwal) > 0) {
        $row_jadwal_cek = mysqli_fetch_assoc($q_cek_jadwal);
        if (!empty($row_jadwal_cek['mapel_ids'])) {
            $has_schedule = true;
        }
    }
    ?>
    <div class="col-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">Absensi Hari Ini</h6>
            </div>
            <div class="card-body text-center">
                <p class="mb-4"><?php echo get_indo_day($today) . ', ' . date('d F Y'); ?></p>
                
                <?php if (!$has_schedule): ?>
                    <div class="card bg-info bg-opacity-10 border border-info text-info p-3 rounded text-center mb-3">
                        <div class="mb-2"><i class="fas fa-calendar-times fa-2x"></i></div>
                        <strong>Hari Libur / Tidak Ada Jadwal Pelajaran</strong><br>
                        Tidak perlu melakukan absensi hari ini.
                    </div>
                    <div class="d-grid gap-2 d-md-block opacity-50" style="pointer-events: none;">
                            <button class="btn btn-secondary btn-lg px-5 mx-2 mb-2" disabled><i class="fas fa-check-circle me-2"></i> HADIR</button>
                            <button class="btn btn-secondary btn-lg px-5 mx-2 mb-2" disabled><i class="fas fa-procedures me-2"></i> SAKIT</button>
                            <button class="btn btn-secondary btn-lg px-5 mx-2 mb-2" disabled><i class="fas fa-envelope-open-text me-2"></i> IZIN</button>
                    </div>

                <?php elseif ($attendance_today_dash): ?>
                    <div class="card bg-success bg-opacity-10 border border-success text-success p-3 rounded">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                Anda sudah melakukan absensi harian.<br>
                                <strong>Status: <?php echo $attendance_today_dash['status']; ?></strong>
                                <?php if ($attendance_today_dash['status'] != 'Hadir' && !empty($attendance_today_dash['keterangan'])): ?>
                                    <br>Keterangan: <?php echo htmlspecialchars($attendance_today_dash['keterangan']); ?>
                                <?php endif; ?>
                                <br>Jam: <?php echo $attendance_today_dash['jam_masuk']; ?>
                            </div>
                        </div>
                        
                        <?php
                        // Show Subject Attendance
                        $q_sub_att = mysqli_query($koneksi, "
                            SELECT a.*, m.nama_mapel 
                            FROM absensi a 
                            JOIN courses c ON a.id_course = c.id_course 
                            JOIN mapel m ON c.id_mapel = m.id_mapel 
                            WHERE a.id_siswa='$uid' AND a.tanggal='$today' AND a.id_course > 0
                        ");

                        if ($q_sub_att && mysqli_num_rows($q_sub_att) > 0) {
                            echo '<hr class="border-success opacity-25 my-3">';
                            echo '<div class="text-start">';
                            echo '<h6 class="font-weight-bold small text-uppercase mb-2 text-success"><i class="fas fa-book me-1"></i> Tercatat di Mata Pelajaran:</h6>';
                            echo '<ul class="list-unstyled mb-0">';
                            while ($row_sub = mysqli_fetch_assoc($q_sub_att)) {
                                echo '<li class="d-flex justify-content-between align-items-center mb-1">';
                                echo '<span>' . htmlspecialchars($row_sub['nama_mapel']) . '</span>';
                                echo '<span class="badge bg-white text-success border border-success rounded-pill px-2">' . $row_sub['status'] . '</span>';
                                echo '</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }
                        ?>
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
                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-bullhorn me-2"></i>Pengumuman Terbaru</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php while($ann = mysqli_fetch_assoc($ann_siswa)): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-info bg-opacity-10 border border-info text-info shadow-sm h-100 p-3 rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="font-weight-bold mb-1"><i class="fas fa-info-circle me-1"></i> <?php echo htmlspecialchars($ann['title'] ?? ''); ?></h5>
                                <small class="text-muted text-nowrap ms-2"><?php echo time_ago_str($ann['created_at']); ?></small>
                            </div>
                            <hr class="my-2 border-info opacity-25">
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($ann['body'] ?? '')); ?></p>
                            <div class="small opacity-75 mt-2 d-flex justify-content-between">
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
                <h6 class="m-0 font-weight-bold text-success">Daftar Asesmen Aktif</h6>
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
                <div class="card bg-info bg-opacity-10 border border-info text-info p-3 rounded text-center">
                    <i class="fas fa-info-circle me-1"></i> Tidak ada ujian yang aktif saat ini.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
