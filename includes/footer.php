            </div> <!-- End container-fluid -->
        </div> <!-- End page-content-wrapper -->
    </div> <!-- End d-flex wrapper -->

    <!-- Footer -->
    <footer class="footer mt-auto py-3 text-center">
        <div class="container">
            <span class="text-muted">
                Copyright &copy; <?php echo date('Y'); ?> <strong>CBT MI Sultan Fattah</strong>. 
                <span class="text-kemenag">Madrasah Hebat Bermartabat.</span>
            </span>
        </div>
    </footer>

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
    </script>
</body>
</html>
