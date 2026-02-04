<?php
include '../../config/database.php';
$page_title = 'Rekap Nilai';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$id_user = $_SESSION['user_id'];
$level = $_SESSION['level'];

if ($level == 'siswa') {
    echo "<script>window.location='../../dashboard.php';</script>";
    exit;
}

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

// Get Filters
$id_ujian = isset($_GET['id_ujian']) ? $_GET['id_ujian'] : '';
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : $single_class_id;

// Query Ujian List
$sql_ujian = "SELECT u.id_ujian, u.nama_ujian, m.nama_mapel, k.nama_kelas 
              FROM ujian u 
              JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal
              JOIN mapel m ON b.id_mapel = m.id_mapel
              JOIN kelas k ON b.id_guru = k.id_kelas -- Assuming bank_soal linked to guru/kelas somehow, or simplify
              ORDER BY u.tgl_mulai DESC";
// Simplified Ujian Query
$sql_ujian = "SELECT u.id_ujian, b.kode_bank AS nama_ujian, m.nama_mapel 
              FROM ujian u 
              JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal
              JOIN mapel m ON b.id_mapel = m.id_mapel
              ORDER BY u.tgl_mulai DESC";
$q_ujian = mysqli_query($koneksi, $sql_ujian);

// Query Kelas List
$sql_kelas = "SELECT * FROM kelas ";
if($level == 'guru' && !empty($guru_kelas_ids)){
    $ids_str = implode(',', $guru_kelas_ids);
    $sql_kelas .= " WHERE id_kelas IN ($ids_str) ";
}
$sql_kelas .= " ORDER BY nama_kelas ASC";
$q_kelas = mysqli_query($koneksi, $sql_kelas);

// Data Query
$data_nilai = [];
if ($id_ujian && $id_kelas) {
    $query = "SELECT s.nisn, s.nama_siswa, us.nilai, us.status, us.waktu_selesai
              FROM siswa s
              LEFT JOIN ujian_siswa us ON s.id_siswa = us.id_siswa AND us.id_ujian = '$id_ujian'
              WHERE s.id_kelas = '$id_kelas'
              ORDER BY s.nama_siswa ASC";
    $result = mysqli_query($koneksi, $query);
    while($row = mysqli_fetch_assoc($result)) {
        $data_nilai[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Rekap Nilai</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Pilih Asesmen</label>
                    <select name="id_ujian" class="form-select" onchange="this.form.submit()" required>
                        <option value="">-- Pilih Asesmen --</option>
                        <?php 
                        mysqli_data_seek($q_ujian, 0);
                        while($u = mysqli_fetch_assoc($q_ujian)): 
                        ?>
                        <option value="<?php echo $u['id_ujian']; ?>" <?php echo ($id_ujian == $u['id_ujian']) ? 'selected' : ''; ?>>
                            <?php echo $u['nama_mapel'] . ' - ' . $u['nama_ujian']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pilih Kelas</label>
                    <select name="id_kelas" class="form-select" onchange="this.form.submit()" <?php echo ($single_class_id) ? 'disabled' : ''; ?> required>
                        <?php if(!$single_class_id): ?>
                        <option value="">-- Pilih Kelas --</option>
                        <?php endif; ?>
                        <?php 
                        mysqli_data_seek($q_kelas, 0);
                        while($k = mysqli_fetch_assoc($q_kelas)): 
                        ?>
                        <option value="<?php echo $k['id_kelas']; ?>" <?php echo ($id_kelas == $k['id_kelas']) ? 'selected' : ''; ?>>
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

    <?php if ($id_ujian && $id_kelas): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Data Nilai Siswa</h6>
            <div>
                <a href="export_excel.php?id_ujian=<?php echo $id_ujian; ?>&id_kelas=<?php echo $id_kelas; ?>" target="_blank" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="export_pdf.php?id_ujian=<?php echo $id_ujian; ?>&id_kelas=<?php echo $id_kelas; ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Nilai</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach($data_nilai as $row): 
                            $nilai = $row['nilai'] ? $row['nilai'] : 0;
                            $status = $row['status'] ? $row['status'] : 'Belum Mengerjakan';
                            if ($status == 'selesai') {
                                $ket = ($nilai >= 75) ? 'TUNTAS' : 'BELUM TUNTAS';
                                $bg_ket = ($nilai >= 75) ? 'success' : 'danger';
                            } else {
                                $ket = '-';
                                $bg_ket = 'secondary';
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $row['nisn']; ?></td>
                            <td><?php echo $row['nama_siswa']; ?></td>
                            <td><?php echo ($status == 'selesai') ? number_format($nilai, 2) : '-'; ?></td>
                            <td>
                                <?php if($status == 'selesai'): ?>
                                    <span class="badge bg-success">Selesai</span>
                                <?php elseif($status == 'sedang_mengerjakan'): ?>
                                    <span class="badge bg-warning text-dark">Sedang Mengerjakan</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum Mengerjakan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $bg_ket; ?>"><?php echo $ket; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>