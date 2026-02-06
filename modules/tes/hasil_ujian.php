<?php
include '../../config/database.php';
$page_title = 'Hasil Asesmen';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$id_user = $_SESSION['user_id'];
$level = $_SESSION['level'];

// Get Guru's Classes
$guru_kelas_ids = [];
$single_class_id = null;
if ($level == 'guru') {
    $q_u = mysqli_query($koneksi, "SELECT mengajar_kelas FROM users WHERE id_user='$id_user'");
    $d_u = mysqli_fetch_assoc($q_u);
    if ($d_u['mengajar_kelas']) {
        $guru_kelas_ids = explode(',', $d_u['mengajar_kelas']);
        if(count($guru_kelas_ids) == 1){
            $single_class_id = $guru_kelas_ids[0];
        }
    }
}

// Filter Logic for Admin/Guru
$where = " WHERE 1=1 ";
if ($level == 'siswa') {
    $where .= " AND us.id_siswa = '$id_user' ";
} else {
    // Admin/Guru filters
    if(isset($_GET['id_kelas']) && !empty($_GET['id_kelas'])) {
        $id_kelas = $_GET['id_kelas'];
        $where .= " AND s.id_kelas = '$id_kelas' ";
    } elseif ($single_class_id) {
        $where .= " AND s.id_kelas = '$single_class_id' ";
        $_GET['id_kelas'] = $single_class_id; // Set for UI
    }
}

// Query Data
if ($level == 'siswa') {
    $query = "SELECT us.*, u.nama_ujian, u.tgl_mulai, m.nama_mapel, m.kktp 
              FROM ujian_siswa us
              JOIN ujian u ON us.id_ujian = u.id_ujian
              JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal
              JOIN mapel m ON b.id_mapel = m.id_mapel
              $where
              ORDER BY us.waktu_selesai DESC";
} else {
    $query = "SELECT us.*, s.nama_siswa, k.nama_kelas, u.nama_ujian, m.nama_mapel, m.kktp 
              FROM ujian_siswa us
              JOIN siswa s ON us.id_siswa = s.id_siswa
              JOIN kelas k ON s.id_kelas = k.id_kelas
              JOIN ujian u ON us.id_ujian = u.id_ujian
              JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal
              JOIN mapel m ON b.id_mapel = m.id_mapel
              $where
              ORDER BY us.waktu_selesai DESC";
}

$result = mysqli_query($koneksi, $query);

// Get Kelas for Filter (Admin only)
if ($level == 'admin' || $level == 'guru') {
    $sql_kelas = "SELECT * FROM kelas ";
    if($level == 'guru' && !empty($guru_kelas_ids)){
        $ids_str = implode(',', $guru_kelas_ids);
        $sql_kelas .= " WHERE id_kelas IN ($ids_str) ";
    }
    $sql_kelas .= " ORDER BY nama_kelas ASC";
    $q_kelas = mysqli_query($koneksi, $sql_kelas);
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Hasil Asesmen</h1>
    </div>

    <?php if ($level == 'admin' || $level == 'guru'): ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter Kelas</label>
                    <select name="id_kelas" class="form-select" onchange="this.form.submit()" <?php echo ($single_class_id) ? 'disabled' : ''; ?>>
                        <?php if(!$single_class_id): ?>
                        <option value="">-- Semua Kelas --</option>
                        <?php endif; ?>
                        <?php while($k = mysqli_fetch_assoc($q_kelas)): ?>
                        <option value="<?php echo $k['id_kelas']; ?>" <?php echo (isset($_GET['id_kelas']) && $_GET['id_kelas'] == $k['id_kelas']) ? 'selected' : ''; ?>>
                            <?php echo $k['nama_kelas']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if($single_class_id): ?>
                    <input type="hidden" name="id_kelas" value="<?php echo $single_class_id; ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <?php if ($level != 'siswa'): ?>
                            <th class="text-center">Nama Siswa</th>
                            <th class="text-center">Kelas</th>
                            <?php endif; ?>
                            <th class="text-center">Mata Pelajaran</th>
                            <th class="text-center">Nama Asesmen</th>
                            <th class="text-center">Nilai</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Waktu Selesai</th>
                            <?php if ($level == 'guru' || $level == 'admin'): ?>
                            <th class="text-center">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($result) > 0):
                            while($row = mysqli_fetch_assoc($result)): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <?php if ($level != 'siswa'): ?>
                            <td><?php echo $row['nama_siswa']; ?></td>
                            <td><?php echo $row['nama_kelas']; ?></td>
                            <?php endif; ?>
                            <td><?php echo $row['nama_mapel']; ?></td>
                            <td><?php echo $row['nama_ujian']; ?></td>
                            <td>
                                <?php $kktp = isset($row['kktp']) ? $row['kktp'] : 75; ?>
                                <span class="badge bg-<?php echo ($row['nilai'] >= $kktp) ? 'success' : 'danger'; ?>">
                                    <?php echo number_format($row['nilai'], 2); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['status'] == 'selesai'): ?>
                                    <span class="badge bg-success">Selesai</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Sedang Mengerjakan</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['waktu_selesai']; ?></td>
                            <?php if ($level == 'guru' || $level == 'admin'): ?>
                            <td class="text-center">
                                <?php if($row['status'] == 'selesai'): ?>
                                <a href="lihat_jawaban.php?id=<?php echo $row['id_ujian_siswa']; ?>" class="btn btn-primary btn-sm" title="Lihat Jawaban">
                                    <i class="fas fa-eye"></i> Lihat Jawaban
                                </a>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="<?php echo ($level != 'siswa') ? '9' : '6'; ?>" class="text-center">Belum ada data hasil asesmen.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
