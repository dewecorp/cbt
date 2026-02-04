<?php
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Get Kelas
$kelas_opt = "";
$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
while($k = mysqli_fetch_assoc($q_kelas)) {
    $sel = (isset($_GET['id_kelas']) && $_GET['id_kelas'] == $k['id_kelas']) ? 'selected' : '';
    $kelas_opt .= "<option value='".$k['id_kelas']."' $sel>".$k['nama_kelas']."</option>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cetak Kartu Ujian</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label class="form-label">Pilih Kelas</label>
                    <select name="id_kelas" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Pilih Kelas --</option>
                        <?php echo $kelas_opt; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <?php if(isset($_GET['id_kelas'])): ?>
                    <a href="print_kartu.php?id_kelas=<?php echo $_GET['id_kelas']; ?>" target="_blank" class="btn btn-success w-100">
                        <i class="fas fa-print"></i> Cetak Semua
                    </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if(isset($_GET['id_kelas'])): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Password (Login)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $id_kelas = $_GET['id_kelas'];
                        $q_siswa = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.id_kelas='$id_kelas' ORDER BY s.nama_siswa ASC");
                        $no = 1;
                        while($s = mysqli_fetch_assoc($q_siswa)):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $s['nisn']; ?></td>
                            <td><?php echo $s['nama_siswa']; ?></td>
                            <td><?php echo $s['nama_kelas']; ?></td>
                            <td>
                                <?php 
                                if (strlen($s['password']) == 60 && substr($s['password'], 0, 4) === '$2y$') {
                                    echo '<span class="badge bg-secondary">Ter-enkripsi</span>';
                                } else {
                                    echo $s['password'];
                                }
                                ?>
                            </td>
                            <td>
                                <a href="print_kartu.php?id_siswa=<?php echo $s['id_siswa']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-print"></i> Cetak
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
