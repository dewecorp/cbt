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
                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-history me-2"></i>Aktivitas Terbaru</h6>
                <a href="modules/laporan/log_aktivitas.php" class="btn btn-sm btn-success">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php
                    $q_log = mysqli_query($koneksi, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5");
                    while($lg = mysqli_fetch_assoc($q_log)):
                        $ico = 'fa-circle';
                        if($lg['action'] == 'login') $ico = 'fa-sign-in-alt';
                        elseif($lg['action'] == 'logout') $ico = 'fa-sign-out-alt';
                        elseif($lg['action'] == 'create') $ico = 'fa-plus';
                        elseif($lg['action'] == 'update') $ico = 'fa-edit';
                        elseif($lg['action'] == 'delete') $ico = 'fa-trash';
                        
                        // Get user name if not in details
                        $who = '';
                        if($lg['user_id'] > 0) {
                            $qu = mysqli_query($koneksi, "SELECT nama_lengkap, level FROM users WHERE id_user='".$lg['user_id']."'");
                            if(mysqli_num_rows($qu) > 0) {
                                $du = mysqli_fetch_assoc($qu);
                                $display_name = $du['nama_lengkap'];
                            } else {
                                // Try siswa
                                $qs = mysqli_query($koneksi, "SELECT nama_siswa FROM siswa WHERE id_siswa='".$lg['user_id']."'");
                                if(mysqli_num_rows($qs) > 0) {
                                    $ds = mysqli_fetch_assoc($qs);
                                    $display_name = $ds['nama_siswa'];
                                }
                            }
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
