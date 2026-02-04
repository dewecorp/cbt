        <!-- Sidebar -->
<div class="sidebar d-flex flex-column p-3 text-white" id="sidebar">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="<?php echo $base_url; ?>dashboard.php" class="d-flex align-items-center text-white text-decoration-none">
            <i class="fas fa-school fa-2x me-2"></i>
            <span class="fs-5 fw-bold">CBT MI</span>
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
                    <span class="text-uppercase small text-white-50 ms-3">Ujian</span>
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
                        <i class="fas fa-poll"></i> Hasil Tes
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
                        <i class="fas fa-poll"></i> Hasil Tes
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
