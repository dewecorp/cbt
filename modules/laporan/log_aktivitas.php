<?php
include '../../config/database.php';

// Cek Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek Level Admin
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: ../../index.php");
    exit;
}

$page_title = 'Log Aktivitas';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Pagination setup
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total
$q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM activity_log");
$total_rows = mysqli_fetch_assoc($q_count)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch data
$q_log = mysqli_query($koneksi, "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Log Aktivitas Sistem</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Riwayat Aktivitas Pengguna</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Waktu</th>
                            <th width="20%">Pengguna</th>
                            <th width="10%">Level</th>
                            <th width="10%">Aksi</th>
                            <th width="15%">Modul</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        if(mysqli_num_rows($q_log) > 0):
                            while($row = mysqli_fetch_assoc($q_log)): 
                                // Determine user display name
                                $who = $row['username'];
                                if($row['user_id'] > 0) {
                                    if ($row['level'] == 'siswa') {
                                        $qs = mysqli_query($koneksi, "SELECT nama_siswa FROM siswa WHERE id_siswa='".$row['user_id']."'");
                                        if($qs && mysqli_num_rows($qs) > 0) {
                                            $who = mysqli_fetch_assoc($qs)['nama_siswa'];
                                        }
                                    } else {
                                        $qu = mysqli_query($koneksi, "SELECT nama_lengkap FROM users WHERE id_user='".$row['user_id']."'");
                                        if($qu && mysqli_num_rows($qu) > 0) {
                                            $who = mysqli_fetch_assoc($qu)['nama_lengkap'];
                                        }
                                    }
                                }
                                
                                $badge_class = 'secondary';
                                if($row['action'] == 'login') $badge_class = 'info';
                                elseif($row['action'] == 'logout') $badge_class = 'dark';
                                elseif($row['action'] == 'create') $badge_class = 'success';
                                elseif($row['action'] == 'update') $badge_class = 'warning';
                                elseif($row['action'] == 'delete') $badge_class = 'danger';
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?>
                                <br>
                                <small class="text-muted"><?php echo time_ago_str($row['created_at']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($who ?? 'Unknown'); ?></td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($row['level'] ?? ''); ?></span></td>
                            <td><span class="badge bg-<?php echo $badge_class; ?>"><?php echo strtoupper($row['action']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['module']); ?></td>
                            <td><?php echo htmlspecialchars($row['details']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data aktivitas.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include '../../includes/footer.php'; ?>
