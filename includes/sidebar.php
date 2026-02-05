        <!-- Sidebar -->
<div class="sidebar d-flex flex-column p-3 text-white" id="sidebar">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <?php
        $school_name = 'CBT MI';
        $school_logo = '';
        $rs = mysqli_query($koneksi, "SELECT nama_sekolah, logo FROM setting LIMIT 1");
        if ($rs && mysqli_num_rows($rs) > 0) {
            $st = mysqli_fetch_assoc($rs);
            if (!empty($st['nama_sekolah'])) $school_name = $st['nama_sekolah'];
            if (!empty($st['logo'])) $school_logo = $st['logo'];
        }
        ?>
        <a href="<?php echo $base_url; ?>dashboard.php" class="d-flex align-items-center text-white text-decoration-none">
            <?php if($school_logo): ?>
                <img src="<?php echo $base_url; ?>assets/img/<?php echo $school_logo; ?>" alt="Logo" class="me-2 sidebar-school-logo">
            <?php endif; ?>
            <span class="fs-6">Elearning Madrasah</span>
        </a>
        <button class="btn btn-link text-white d-md-none" id="sidebarClose">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>
    <hr class="mt-0">
    <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="<?php echo $base_url; ?>dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <?php $level = isset($_SESSION['level']) ? $_SESSION['level'] : ''; ?>
                <?php if($level === 'admin'): ?>
                <li class="nav-item mt-2">
                    <span class="text-uppercase small text-white-50 ms-3">Master Data</span>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/guru.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'guru.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard-teacher"></i> Data Guru
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/siswa.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'siswa.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i> Data Siswa
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/kelas.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'kelas.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-school"></i> Data Kelas
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/master/mapel.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'mapel.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Mata Pelajaran
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item mt-2">
                    <span class="text-uppercase small text-white-50 ms-3">Elearning</span>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/courses.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/courses.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i> Kelas Online
                    </a>
                </li>
                <?php if($level !== 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/materials.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/materials.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-book-open"></i> Materi
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level === 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/student_assignments.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'student_assignments.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane"></i> Kirim Tugas
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/student_grades.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'student_grades.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Nilai Tugas
                    </a>
                </li>
                <?php else: ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/assignments.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/assignments.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i> Tugas
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level !== 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/forum.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/forum.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i> Forum
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level !== 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/elearning/announcements.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'elearning/announcements.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-bullhorn"></i> Pengumuman
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item mt-2">
                    <span class="text-uppercase small text-white-50 ms-3">Asesmen</span>
                </li>
                
                <?php if($level === 'admin' || $level === 'guru'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/bank_soal.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'bank_soal.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i> Bank Soal
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/jadwal_ujian.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'jadwal_ujian.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Administrasi Tes
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/hasil_ujian.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'hasil_ujian.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-poll"></i> Hasil Asesmen
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/rekap_nilai.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'rekap_nilai.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i> Rekap Nilai
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/cetak/kartu_ujian.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'kartu_ujian.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-print"></i> Cetak Kartu
                    </a>
                </li>
                <?php endif; ?>

                <?php if($level === 'siswa'): ?>
                <li>
                    <a href="<?php echo $base_url; ?>modules/tes/hasil_ujian.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'hasil_ujian.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-poll"></i> Hasil Asesmen
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/cetak/kartu_ujian.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'kartu_ujian.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-print"></i> Cetak Kartu
                    </a>
                </li>
                <?php endif; ?>


                <?php if($level === 'admin'): ?>
                <li class="nav-item mt-2">
                    <span class="text-uppercase small text-white ms-3">System</span>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/pengaturan/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'pengaturan') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i> Pengaturan
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/users/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'users') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog"></i> Pengguna
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>modules/backup/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'backup') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-hdd"></i> Backup Restore
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item mt-4 mb-5">
                    <a href="<?php echo $base_url; ?>logout.php" class="nav-link bg-danger text-white">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
    </ul>
    <hr>
    <!-- Dropdown User removed from sidebar, moved to Navbar -->
</div>
