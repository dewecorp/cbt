<?php
// Hitung Data untuk Dashboard (Admin)
$q_guru_count = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM users WHERE level='guru'");
$jml_guru = mysqli_fetch_assoc($q_guru_count)['count'];

$q_siswa_count = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM siswa WHERE status='aktif'");
$jml_siswa = mysqli_fetch_assoc($q_siswa_count)['count'];

$q_kelas_count = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM kelas");
$jml_kelas = mysqli_fetch_assoc($q_kelas_count)['count'];

$q_ujian_count = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM ujian WHERE status='aktif' AND NOW() BETWEEN tgl_mulai AND tgl_selesai");
$jml_ujian = mysqli_fetch_assoc($q_ujian_count)['count'];

// Admin welcome text
$admin_welcome_text = "Aplikasi Computer Based Test (CBT) ini dirancang untuk memudahkan pelaksanaan ujian di MI Sultan Fattah Sukosono. Silahkan gunakan menu di samping untuk mengelola data dan ujian.";
$q_setting_dash = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$d_setting_dash = $q_setting_dash ? mysqli_fetch_assoc($q_setting_dash) : null;
if ($d_setting_dash && isset($d_setting_dash['admin_welcome_text']) && !empty($d_setting_dash['admin_welcome_text'])) {
    $admin_welcome_text = $d_setting_dash['admin_welcome_text'];
}
?>

<div class="row">
    <!-- Data Guru Widget -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Data Guru</div>
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
        <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Data Kelas</div>
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
                <h6 class="m-0 font-weight-bold text-success">Selamat Datang di E-Learning MI Sultan Fattah Sukosono</h6>
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
                <?php
                $q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM activity_log");
                $total_log = mysqli_fetch_assoc($q_total)['total'];
                ?>
                <h6 class="m-0 font-weight-bold text-primary">Aktivitas Terbaru <span class="small text-muted fw-normal ms-2">Total: <?php echo $total_log; ?></span></h6>
            </div>
            <div class="card-body">
                <div class="timeline" style="max-height: 500px; overflow-y: auto; padding-left: 2rem;">
                <?php
                $q_log = mysqli_query($koneksi, "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 20");
                while($lg = mysqli_fetch_assoc($q_log)):
                    $ico = 'fa-circle';
                    $text_class = 'text-blue';
                    $border_class = 'border-left-blue';
                    $marker_color = '#4e73df';
                    
                    if($lg['action'] == 'login') {
                        $ico = 'fa-sign-in-alt';
                        $text_class = 'text-blue';
                        $border_class = 'border-left-blue';
                        $marker_color = '#4e73df';
                    } elseif($lg['action'] == 'logout') {
                        $ico = 'fa-sign-out-alt';
                        $text_class = 'text-danger';
                        $border_class = 'border-left-danger';
                        $marker_color = '#e74a3b';
                    } elseif($lg['action'] == 'create') {
                        $ico = 'fa-plus';
                        $text_class = 'text-success';
                        $border_class = 'border-left-success';
                        $marker_color = '#1cc88a';
                    } elseif($lg['action'] == 'update') {
                        $ico = 'fa-edit';
                        $text_class = 'text-warning';
                        $border_class = 'border-left-warning';
                        $marker_color = '#f6c23e';
                    } elseif($lg['action'] == 'delete') {
                        $ico = 'fa-trash';
                        $text_class = 'text-danger';
                        $border_class = 'border-left-danger';
                        $marker_color = '#e74a3b';
                    } elseif($lg['action'] == 'import') {
                        $ico = 'fa-file-excel';
                        $text_class = 'text-success';
                        $border_class = 'border-left-success';
                        $marker_color = '#1cc88a';
                    } else {
                         // Default fallback for unknown actions
                        $ico = 'fa-circle';
                        $text_class = 'text-secondary';
                        $border_class = 'border-left-secondary';
                        $marker_color = '#858796';
                    }
                    
                    // Get user name if not in details
                    $display_name = '';
                    $level = $lg['level'] ?? '';
                    
                    if($lg['user_id'] > 0) {
                        // Check level from log to decide which table to query
                        if ($level == 'siswa') {
                            $qs = mysqli_query($koneksi, "SELECT nama_siswa FROM siswa WHERE id_siswa='".$lg['user_id']."'");
                            if($qs && mysqli_num_rows($qs) > 0) {
                                $ds = mysqli_fetch_assoc($qs);
                                $display_name = $ds['nama_siswa'];
                            }
                        } else {
                            // Default to users table (admin/guru)
                            $qu = mysqli_query($koneksi, "SELECT nama_lengkap, level FROM users WHERE id_user='".$lg['user_id']."'");
                            if($qu && mysqli_num_rows($qu) > 0) {
                                $du = mysqli_fetch_assoc($qu);
                                $display_name = $du['nama_lengkap'];
                                // Only set level if it was missing in log
                                if(empty($level)) $level = $du['level'];
                            } else {
                                // Fallback: if not found in users and level is unknown, try siswa
                                if (empty($level)) {
                                    $qs = mysqli_query($koneksi, "SELECT nama_siswa FROM siswa WHERE id_siswa='".$lg['user_id']."'");
                                    if($qs && mysqli_num_rows($qs) > 0) {
                                        $ds = mysqli_fetch_assoc($qs);
                                        $display_name = $ds['nama_siswa'];
                                        $level = 'siswa';
                                    }
                                }
                            }
                        }
                    }
                    
                    $who_text = $display_name;
                    if(!empty($level)) $who_text .= " ($level)";
                    
                    $time_ago = time_ago_str($lg['created_at']);
                    $dt = date('d/m/Y H:i:s', strtotime($lg['created_at'])) . ' • ' . $time_ago;
                ?>
                <div class="timeline-item">
                    <div class="timeline-marker" style="background-color: <?php echo $marker_color; ?>"></div>
                    <div class="timeline-content card <?php echo $border_class; ?> shadow-sm">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="<?php echo $text_class; ?> fw-bold text-uppercase">
                                    <i class="fas <?php echo $ico; ?> me-2"></i> <?php echo strtoupper($lg['action']); ?>
                                </div>
                                <div class="text-muted small">
                                    <i class="far fa-clock me-1"></i> <?php echo $time_ago; ?>
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-secondary me-1"><?php echo strtolower($lg['module']); ?></span>
                                <span class="fw-bold text-dark"><?php echo $who_text; ?></span>
                                <span class="text-dark ms-1"><?php echo htmlspecialchars($lg['details']); ?></span>
                            </div>
                            <div class="text-muted small">
                                <?php echo $dt; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>
