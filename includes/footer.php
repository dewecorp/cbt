            </div> <!-- End container-fluid -->
        </div> <!-- End page-content-wrapper -->
    </div> <!-- End d-flex wrapper -->

    <!-- Footer -->
    <footer class="footer mt-auto py-3 text-center">
        <div class="container">
            <span class="text-muted">
                Copyright &copy; <?php echo date('Y'); ?> <strong>E-Learning MI Sultan Fattah</strong>. 
                <span class="text-kemenag">Madrasah Hebat Bermartabat.</span>
            </span>
        </div>
    </footer>

    <!-- Bottom Navigation (Mobile Only) -->
    <nav class="bottom-nav d-md-none">
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']);
        $is_dashboard = ($current_page == 'dashboard.php' || $current_page == 'teacher.php' || $current_page == 'admin.php' || $current_page == 'student.php');
        $is_courses = (strpos($_SERVER['PHP_SELF'], 'elearning/courses.php') !== false);
        
        $level = isset($_SESSION['level']) ? $_SESSION['level'] : '';
        $role_param = $level ? '?role='.$level : '';
        $dashboard_url = 'dashboard.php';
        if ($level === 'admin') {
            $dashboard_url = 'admin.php';
        } elseif ($level === 'guru') {
            $dashboard_url = 'teacher.php';
        } elseif ($level === 'siswa') {
            $dashboard_url = 'student.php';
        }
        ?>
        
        <a href="<?php echo $base_url . $dashboard_url . $role_param; ?>" class="nav-item <?php echo $is_dashboard ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Beranda</span>
        </a>
        
        <a href="<?php echo $base_url; ?>modules/elearning/courses.php<?php echo $role_param; ?>" class="nav-item <?php echo $is_courses ? 'active' : ''; ?>">
            <i class="fas fa-layer-group"></i>
            <span>Kelas</span>
        </a>

        <?php if($level === 'siswa'): ?>
            <?php 
            $is_tugas = (strpos($_SERVER['PHP_SELF'], 'student_assignments.php') !== false);
            $is_nilai = (strpos($_SERVER['PHP_SELF'], 'student_grades.php') !== false);
            ?>
            <a href="<?php echo $base_url; ?>modules/elearning/student_assignments.php<?php echo $role_param; ?>" class="nav-item <?php echo $is_tugas ? 'active' : ''; ?>">
                <i class="fas fa-paper-plane"></i>
                <span>Tugas</span>
            </a>
            <a href="<?php echo $base_url; ?>modules/elearning/student_grades.php<?php echo $role_param; ?>" class="nav-item <?php echo $is_nilai ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Nilai</span>
            </a>
        <?php else: ?>
            <?php 
            $is_materi = (strpos($_SERVER['PHP_SELF'], 'elearning/materials.php') !== false);
            $is_tugas_g = (strpos($_SERVER['PHP_SELF'], 'elearning/assignments.php') !== false);
            ?>
            <a href="<?php echo $base_url; ?>modules/elearning/materials.php<?php echo $role_param; ?>" class="nav-item <?php echo $is_materi ? 'active' : ''; ?>">
                <i class="fas fa-book-open"></i>
                <span>Materi</span>
            </a>
            <a href="<?php echo $base_url; ?>modules/elearning/assignments.php<?php echo $role_param; ?>" class="nav-item <?php echo $is_tugas_g ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Tugas</span>
            </a>
        <?php endif; ?>

        <a href="javascript:void(0);" class="nav-item" onclick="confirmAction('<?php echo $base_url; ?>logout.php?role=<?php echo $level; ?>','Keluar dari aplikasi?','Keluar'); return false;">
            <i class="fas fa-user"></i>
            <span>Akun</span>
        </a>
    </nav>

    <!-- Mobile Menu Modal -->
    <div class="modal fade" id="mobileMenuModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-th-large me-2"></i> Menu Lengkap</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if($level === 'admin'): ?>
                            <div class="list-group-item bg-light fw-bold text-uppercase small text-muted">Master Data</div>
                            <a href="<?php echo $base_url; ?>modules/master/guru.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-chalkboard-teacher me-2 text-success"></i> Data Guru</a>
                            <a href="<?php echo $base_url; ?>modules/master/siswa.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-user-graduate me-2 text-success"></i> Data Siswa</a>
                            <a href="<?php echo $base_url; ?>modules/master/kelas.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-school me-2 text-success"></i> Data Kelas</a>
                            <a href="<?php echo $base_url; ?>modules/master/mapel.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-book me-2 text-success"></i> Mata Pelajaran</a>
                        <?php endif; ?>

                        <div class="list-group-item bg-light fw-bold text-uppercase small text-muted">E-Learning</div>
                        <?php if($level !== 'siswa'): ?>
                            <a href="<?php echo $base_url; ?>modules/elearning/forum.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-comments me-2 text-success"></i> Forum</a>
                        <?php endif; ?>
                        <?php if($level === 'guru'): ?>
                            <a href="<?php echo $base_url; ?>modules/elearning/announcements.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-bullhorn me-2 text-success"></i> Pengumuman</a>
                        <?php endif; ?>

                        <div class="list-group-item bg-light fw-bold text-uppercase small text-muted">Asesmen</div>
                        <?php if($level === 'admin' || $level === 'guru'): ?>
                            <a href="<?php echo $base_url; ?>modules/tes/bank_soal.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-database me-2 text-success"></i> Bank Soal</a>
                            <a href="<?php echo $base_url; ?>modules/tes/jadwal_ujian.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-calendar-alt me-2 text-success"></i> Administrasi Tes</a>
                            <a href="<?php echo $base_url; ?>modules/tes/hasil_ujian.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-poll me-2 text-success"></i> Hasil Asesmen</a>
                            <a href="<?php echo $base_url; ?>modules/tes/rekap_nilai.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-file-alt me-2 text-success"></i> Rekap Nilai</a>
                            <a href="<?php echo $base_url; ?>modules/cetak/kartu_ujian.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-print me-2 text-success"></i> Cetak Kartu</a>
                        <?php endif; ?>
                        <?php if($level === 'siswa'): ?>
                             <a href="<?php echo $base_url; ?>modules/tes/hasil_ujian.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-poll me-2 text-success"></i> Hasil Asesmen</a>
                             <a href="<?php echo $base_url; ?>modules/cetak/kartu_ujian.php<?php echo $role_param; ?>" class="list-group-item list-group-item-action"><i class="fas fa-print me-2 text-success"></i> Cetak Kartu</a>
                        <?php endif; ?>

                         <div class="list-group-item bg-light fw-bold text-uppercase small text-muted">Akun</div>
                         <a href="<?php echo $base_url; ?>logout.php?role=<?php echo $level; ?>" class="list-group-item list-group-item-action text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo $base_url; ?>assets/vendor/jquery/jquery.min.js"></script>
    <script src="<?php echo $base_url; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="<?php echo $base_url; ?>assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="<?php echo $base_url; ?>assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?php echo $base_url; ?>assets/vendor/datatables/js/dataTables.responsive.min.js"></script>
    <script src="<?php echo $base_url; ?>assets/vendor/datatables/js/responsive.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize all datatables
            $('.table-datatable').DataTable({
                responsive: true,
                language: {
                    // Use local file or english default if not available
                    // url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' 
                    // Manual translation to avoid CDN call
                    "sEmptyTable":   "Tidak ada data yang tersedia pada tabel ini",
                    "sProcessing":   "Sedang memproses...",
                    "sLengthMenu":   "Tampilkan _MENU_ entri",
                    "sZeroRecords":  "Tidak ditemukan data yang sesuai",
                    "sInfo":         "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    "sInfoEmpty":    "Menampilkan 0 sampai 0 dari 0 entri",
                    "sInfoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                    "sInfoPostFix":  "",
                    "sSearch":       "Cari:",
                    "sUrl":          "",
                    "oPaginate": {
                        "sFirst":    "Pertama",
                        "sPrevious": "Sebelumnya",
                        "sNext":     "Selanjutnya",
                        "sLast":     "Terakhir"
                    }
                }
            });

            // Mobile sidebar toggle fix
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('d-none');
            });
        });

        // Function for SweetAlert confirmation
        function confirmDelete(url) {
            Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            })
        }

        function confirmAction(url, message, confirmText) {
            Swal.fire({
                title: 'Konfirmasi',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmText,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            })
        }
    </script>
</body>
</html>
