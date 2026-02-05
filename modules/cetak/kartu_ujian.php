<?php
include '../../config/database.php';
$page_title = 'Cetak Kartu Asesmen';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Get Guru's Classes
$id_user = $_SESSION['user_id'];
$level = $_SESSION['level'];
$guru_kelas_ids = [];
$single_class_id = null;

if ($level == 'guru') {
    $q_u = mysqli_query($koneksi, "SELECT mengajar_kelas FROM users WHERE id_user='$id_user'");
    $d_u = mysqli_fetch_assoc($q_u);
    if ($d_u['mengajar_kelas']) {
        $guru_kelas_ids = explode(',', $d_u['mengajar_kelas']);
        if(count($guru_kelas_ids) == 1){
            $single_class_id = $guru_kelas_ids[0];
            if(!isset($_GET['id_kelas'])){
                $_GET['id_kelas'] = $single_class_id;
            }
        }
    }
}

// Get Kelas
$sql_kelas = "SELECT * FROM kelas ";
if($level == 'guru' && !empty($guru_kelas_ids)){
    $ids_str = implode(',', $guru_kelas_ids);
    $sql_kelas .= " WHERE id_kelas IN ($ids_str) ";
}
$sql_kelas .= " ORDER BY nama_kelas ASC";
$q_kelas = mysqli_query($koneksi, $sql_kelas);

$kelas_opt = "";
while($k = mysqli_fetch_assoc($q_kelas)) {
    $sel = (isset($_GET['id_kelas']) && $_GET['id_kelas'] == $k['id_kelas']) ? 'selected' : '';
    $kelas_opt .= "<option value='".$k['id_kelas']."' $sel>".$k['nama_kelas']."</option>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cetak Kartu Asesmen</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if($level == 'siswa'): ?>
                <?php
                // Logika khusus Siswa
                $id_siswa = $_SESSION['user_id'];
                $q_me = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.id_siswa='$id_siswa'");
                $me = mysqli_fetch_assoc($q_me);
                ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Berikut adalah kartu ujian Anda. Silahkan klik tombol cetak untuk mengunduh atau mencetak kartu ujian.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Nama Lengkap</th>
                            <td><?php echo $me['nama_siswa']; ?></td>
                        </tr>
                        <tr>
                            <th>NISN</th>
                            <td><?php echo $me['nisn']; ?></td>
                        </tr>
                        <tr>
                            <th>Kelas</th>
                            <td><?php echo $me['nama_kelas']; ?></td>
                        </tr>
                        <tr>
                            <th>Password Login</th>
                            <td>
                                <?php 
                                if (strlen($me['password']) == 60 && substr($me['password'], 0, 4) === '$2y$') {
                                    echo '<span class="badge bg-secondary">Ter-enkripsi</span>';
                                } else {
                                    echo $me['password'];
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Aksi</th>
                            <td>
                                <a href="print_kartu.php?id_siswa=<?php echo $me['id_siswa']; ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-print"></i> Cetak Kartu Asesmen
                                </a>
                            </td>
                        </tr>
                    </table>
                </div>

            <?php else: ?>
                <!-- Tampilan Admin/Guru -->
                <form method="GET" action="" class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Pilih Kelas</label>
                        <select name="id_kelas" class="form-select" required onchange="this.form.submit()" <?php echo ($single_class_id) ? 'disabled' : ''; ?>>
                            <?php if(!$single_class_id): ?>
                            <option value="">-- Pilih Kelas --</option>
                            <?php endif; ?>
                            <?php echo $kelas_opt; ?>
                        </select>
                        <?php if($single_class_id): ?>
                        <input type="hidden" name="id_kelas" value="<?php echo $single_class_id; ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-auto d-flex align-items-end">
                        <?php if(isset($_GET['id_kelas'])): ?>
                        <a href="print_kartu.php?id_kelas=<?php echo $_GET['id_kelas']; ?>" target="_blank" class="btn btn-success">
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
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
