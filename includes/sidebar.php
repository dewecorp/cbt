        <!-- Sidebar -->
<div class="sidebar d-flex flex-column p-3 text-white" id="sidebar">
    <div class="sidebar-brand-header flex-shrink-0">
    <div class="d-flex align-items-center justify-content-between mb-3 px-3 pt-3">
        <?php
        $school_name = 'CBT MI';
        $school_logo = '';
        $rs = mysqli_query($koneksi, "SELECT nama_sekolah, logo FROM setting LIMIT 1");
        if ($rs && mysqli_num_rows($rs) > 0) {
            $st = mysqli_fetch_assoc($rs);
            if (!empty($st['nama_sekolah'])) $school_name = $st['nama_sekolah'];
            if (!empty($st['logo'])) $school_logo = $st['logo'];
        }
        
        // Determine Dashboard URL based on level
        $dashboard_url = 'dashboard.php';
        $level = isset($_SESSION['level']) ? $_SESSION['level'] : '';
        $role_param = $level ? '?role=' . $level : ''; // Generate role param
        
        if ($level === 'admin') $dashboard_url = 'admin.php' . $role_param;
        elseif ($level === 'guru') $dashboard_url = 'teacher.php' . $role_param;
        elseif ($level === 'siswa') $dashboard_url = 'student.php' . $role_param;
        else $dashboard_url = 'dashboard.php' . $role_param;

        $script_base = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
        $path_self = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
        $dash_basename = basename(explode('?', $dashboard_url)[0]);
        $nav_dashboard_active = ($script_base === $dash_basename || $script_base === 'dashboard.php');

        // Sub-halaman modules/tes/* agar item Asesmen bisa active; gulir sidebar (#sidebar) menjaga menu aktif tetap terlihat
        $sb_bank = ['bank_soal.php', 'buat_soal.php', 'export_pdf.php', 'export_excel.php', 'download_template_soal.php', 'download_template_word.php', 'import_word_helper.php', 'check_db.php', 'add_column_bobot.php'];
        $sb_jadwal = ['jadwal_ujian.php', 'monitoring_ujian.php', 'kerjakan.php', 'konfirmasi.php', 'simpan_jawaban.php', 'keep_alive.php', 'set_ragu.php'];
        $sb_hasil = ['hasil_ujian.php', 'lihat_jawaban.php', 'selesai_ujian.php'];
        $sb_rekap = ['rekap_nilai.php'];
        $active_tes_bank = in_array($script_base, $sb_bank, true);
        $active_tes_jadwal = in_array($script_base, $sb_jadwal, true);
        $active_tes_hasil = in_array($script_base, $sb_hasil, true);
        $active_tes_rekap = in_array($script_base, $sb_rekap, true);
        $active_tes_kartu = (strpos($path_self, 'kartu_ujian.php') !== false || strpos($path_self, 'print_kartu.php') !== false);
        if ($level === 'siswa' && in_array($script_base, ['kerjakan.php', 'konfirmasi.php'], true)) {
            $active_tes_hasil = true;
        }
        ?>
        <a href="<?php echo $base_url . $dashboard_url; ?>" class="d-flex align-items-center text-white text-decoration-none">
            <?php if($school_logo): ?>
                <img src="<?php echo $base_url; ?>assets/img/<?php echo $school_logo; ?>" alt="Logo" class="me-2 sidebar-school-logo">
            <?php endif; ?>
            <span class="fs-4 fw-bold">E-Learning</span>
        </a>
        <button class="btn btn-link text-white d-md-none" id="sidebarClose">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>
    <hr class="mt-0 mx-3">
    </div>
    <div class="sidebar-nav-scroll">
    <ul class="nav nav-pills flex-column mb-0 px-3">
                <li class="nav-item">
                    <a href="<?php echo $base_url . $dashboard_url; ?>" class="nav-link <?php echo $nav_dashboard_active ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <?php $level = isset($_SESSION['level']) ? $_SESSION['level'] : ''; ?>
                <?php if($level === 'admin'): ?>
                <li class="nav-item mt-2">
                    <span class="text-uppercase small text-white-50 ms-3">Master Data</span>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/guru.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'guru.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard-teacher"></i> Data Guru
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/siswa.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'siswa.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i> Data Siswa
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/kelas.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'kelas.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-school"></i> Data Kelas
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/mapel.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'mapel.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Mata Pelajaran
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/jadwal_pelajaran.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'jadwal_pelajaran.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Jadwal Pelajaran
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item mt-2">
                    <span class="text-uppercase small text-white-50 ms-3">E-Learning</span>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/courses.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/courses.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i> Kelas Online
                    </a>
                </li>
                <?php if($level !== 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/materials.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/materials.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-book-open"></i> Materi
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level === 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/student_assignments.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'student_assignments.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane"></i> Kirim Tugas
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/student_grades.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'student_grades.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Nilai Tugas
                    </a>
                </li>
                <?php else: ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/assignments.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/assignments.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i> Tugas
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/rekap_absensi.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/rekap_absensi.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Kehadiran Siswa
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level !== 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/forum.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/forum.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i> Forum
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level === 'guru'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/announcements.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/announcements.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-bullhorn"></i> Pengumuman
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level === 'admin' || $level === 'guru' || $level === 'siswa'): ?>
                <li class="nav-item mt-2">
                    <span class="text-uppercase small text-white-50 ms-3">Asesmen</span>
                </li>
                <?php endif; ?>
                <?php if($level === 'admin' || $level === 'guru'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/bank_soal.php<?php echo $role_param; ?>" class="nav-link <?php echo $active_tes_bank ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i> Bank Soal
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/jadwal_ujian.php<?php echo $role_param; ?>" class="nav-link <?php echo $active_tes_jadwal ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Jadwal Asesmen
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/hasil_ujian.php<?php echo $role_param; ?>" class="nav-link <?php echo $active_tes_hasil ? 'active' : ''; ?>">
                        <i class="fas fa-poll"></i> Hasil Asesmen
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/rekap_nilai.php<?php echo $role_param; ?>" class="nav-link <?php echo $active_tes_rekap ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i> Rekap Nilai
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/cetak/kartu_ujian.php<?php echo $role_param; ?>" class="nav-link <?php echo $active_tes_kartu ? 'active' : ''; ?>">
                        <i class="fas fa-print"></i> Cetak Kartu
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level === 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/hasil_ujian.php<?php echo $role_param; ?>" class="nav-link <?php echo $active_tes_hasil ? 'active' : ''; ?>">
                        <i class="fas fa-poll"></i> Hasil Asesmen
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/cetak/kartu_ujian.php<?php echo $role_param; ?>" class="nav-link <?php echo $active_tes_kartu ? 'active' : ''; ?>">
                        <i class="fas fa-print"></i> Cetak Kartu
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level === 'admin'): ?>
                <li class="nav-item mt-2">
                    <span class="text-uppercase small text-white ms-3">System</span>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/pengaturan/index.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'pengaturan') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i> Pengaturan
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/users/index.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'users') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog"></i> Pengguna
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/backup/index.php<?php echo $role_param; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'backup') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-hdd"></i> Backup Restore
                    </a>
                </li>
                <?php endif; ?>
    </ul>

    <ul class="nav nav-pills flex-column flex-shrink-0 mb-2 mt-1 px-3 pb-2">
                <li class="nav-item">
                    <a href="javascript:void(0);" onclick="confirmAction('<?php echo $base_url; ?>logout.php?role=<?php echo $level; ?>','Keluar dari aplikasi?','Keluar');" class="nav-link bg-danger text-white">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
    </ul>
    <hr>
    </div>
    <!-- Dropdown User removed from sidebar, moved to Navbar -->
</div>
