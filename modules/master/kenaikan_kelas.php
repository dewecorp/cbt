<?php
include '../../config/database.php';
$page_title = 'Kenaikan Kelas';
include '../../includes/header.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    echo "<script>window.location='../../dashboard.php';</script>";
    exit;
}

function kk_to_roman($num) {
    $map = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
    $num = (int)$num;
    return isset($map[$num]) ? $map[$num] : (string)$num;
}
function kk_label($nama) {
    $t = trim((string)$nama);
    if (ctype_digit($t)) return kk_to_roman((int)$t);
    if (preg_match('/^(\d{1,2})/', $t, $m)) return kk_to_roman((int)$m[1]) . trim(substr($t, strlen($m[1])));
    return $t;
}
function kk_next_ta($ta) {
    $ta = trim((string)$ta);
    if (preg_match('/(\d{4})\s*\/\s*(\d{4})/', $ta, $m)) {
        return ((int)$m[1] + 1) . '/' . ((int)$m[2] + 1);
    }
    $y = (int)date('Y');
    return $y . '/' . ($y + 1);
}
function kk_tingkat($nama) {
    $t = trim((string)$nama);
    if (preg_match('/(\d{1,2})/', $t, $m)) return (int)$m[1];
    $roman = ['XII' => 12, 'XI' => 11, 'X' => 10, 'IX' => 9, 'VIII' => 8, 'VII' => 7, 'VI' => 6, 'V' => 5, 'IV' => 4, 'III' => 3, 'II' => 2, 'I' => 1];
    $up = strtoupper($t);
    foreach ($roman as $r => $n) {
        if (strpos($up, $r) !== false) return $n;
    }
    return 0;
}

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS kenaikan_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    dari_kelas INT NOT NULL,
    ke_kelas INT NOT NULL,
    ta_asal VARCHAR(20) NULL,
    ta_tujuan VARCHAR(20) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siswa (id_siswa),
    INDEX idx_kelas (dari_kelas, ke_kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$setting = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT tahun_ajaran FROM setting LIMIT 1"));
$ta_asal = isset($setting['tahun_ajaran']) && trim($setting['tahun_ajaran']) !== '' ? substr(trim($setting['tahun_ajaran']), 0, 20) : date('Y') . '/' . (date('Y') + 1);
$ta_tujuan = kk_next_ta($ta_asal);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_naik'])) {
    $asal = isset($_POST['id_kelas_asal']) ? (int)$_POST['id_kelas_asal'] : 0;
    $tujuan = isset($_POST['id_kelas_tujuan']) ? (int)$_POST['id_kelas_tujuan'] : 0;
    $ta_p_asal = $ta_asal;
    $ta_p_tujuan = $ta_tujuan;
    $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
    if ($asal <= 0 || $tujuan <= 0 || empty($ids)) {
        echo "<script>Swal.fire('Gagal', 'Pilih kelas dan centang minimal 1 siswa.', 'error');</script>";
    } else {
        $id_list = implode(',', $ids);
        if (mysqli_query($koneksi, "UPDATE siswa SET id_kelas='$tujuan' WHERE id_siswa IN ($id_list)")) {
            $n = count($ids);
            $ea = mysqli_real_escape_string($koneksi, $ta_p_asal);
            $et = mysqli_real_escape_string($koneksi, $ta_p_tujuan);
            foreach ($ids as $sid) {
                $sid = (int)$sid;
                mysqli_query($koneksi, "INSERT INTO kenaikan_log (id_siswa, dari_kelas, ke_kelas, ta_asal, ta_tujuan) VALUES ('$sid', '$asal', '$tujuan', '$ea', '$et')");
            }
            log_activity('update', 'kenaikan_kelas', "naik kelas $n siswa dari $asal ke $tujuan ($ta_p_asal -> $ta_p_tujuan)");
            echo "<script>
                Swal.fire({icon:'success', title:'Berhasil', text:'$n siswa berhasil naik kelas', timer:1500, showConfirmButton:false})
                .then(() => { window.location.href = 'kenaikan_kelas.php?kelas_asal=$asal'; });
            </script>";
        } else {
            echo "<script>Swal.fire('Gagal', 'Gagal memproses: " . addslashes(mysqli_error($koneksi)) . "', 'error');</script>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['batal_naik'])) {
    $asal = isset($_POST['id_kelas_asal']) ? (int)$_POST['id_kelas_asal'] : 0;
    $tujuan = isset($_POST['id_kelas_tujuan']) ? (int)$_POST['id_kelas_tujuan'] : 0;
    $ta_p_asal = $ta_asal;
    $ids = isset($_POST['batal_ids']) && is_array($_POST['batal_ids']) ? array_map('intval', $_POST['batal_ids']) : [];
    if ($asal <= 0 || $tujuan <= 0 || empty($ids)) {
        echo "<script>Swal.fire('Gagal', 'Centang minimal 1 siswa di kelas tujuan.', 'error');</script>";
    } else {
        $id_list = implode(',', $ids);
        if (mysqli_query($koneksi, "UPDATE siswa SET id_kelas='$asal' WHERE id_siswa IN ($id_list) AND id_kelas='$tujuan'")) {
            $n = mysqli_affected_rows($koneksi);
            foreach ($ids as $sid) {
                $sid = (int)$sid;
                mysqli_query($koneksi, "DELETE FROM kenaikan_log WHERE id_siswa='$sid' AND dari_kelas='$asal' AND ke_kelas='$tujuan' ORDER BY id DESC LIMIT 1");
            }
            log_activity('update', 'kenaikan_kelas', "batal naik $n siswa dari $tujuan kembali ke $asal ($ta_p_asal)");
            echo "<script>
                Swal.fire({icon:'success', title:'Berhasil', text:'$n siswa dikembalikan ke kelas asal', timer:1500, showConfirmButton:false})
                .then(() => { window.location.href = 'kenaikan_kelas.php?kelas_asal=$asal'; });
            </script>";
        } else {
            echo "<script>Swal.fire('Gagal', 'Gagal membatalkan: " . addslashes(mysqli_error($koneksi)) . "', 'error');</script>";
        }
    }
}

$qk = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$kelas_list = [];
while ($k = mysqli_fetch_assoc($qk)) $kelas_list[] = $k;
usort($kelas_list, function ($a, $b) {
    $ta = kk_tingkat($a['nama_kelas']);
    $tb = kk_tingkat($b['nama_kelas']);
    if ($ta == $tb) return strcmp($a['nama_kelas'], $b['nama_kelas']);
    return $ta < $tb ? -1 : 1;
});

$asal_id = isset($_GET['kelas_asal']) ? (int)$_GET['kelas_asal'] : 0;
$asal_row = null;
$tujuan_row = null;
if ($asal_id > 0) {
    foreach ($kelas_list as $i => $kl) {
        if ((int)$kl['id_kelas'] === $asal_id) {
            $asal_row = $kl;
            if (isset($kelas_list[$i + 1])) $tujuan_row = $kelas_list[$i + 1];
            break;
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 mb-0">Kenaikan Kelas</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="../../admin.php?role=admin" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item">Master Data</li>
                <li class="breadcrumb-item active">Kenaikan Kelas</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success"><?php echo htmlspecialchars($ta_asal); ?> Tahun Ajaran Asal</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <label class="form-label fw-semibold small">Kelas</label>
                        <select class="form-select" name="kelas_asal" onchange="this.form.submit()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas_list as $kl): ?>
                                <option value="<?php echo $kl['id_kelas']; ?>" <?php echo ($asal_id == $kl['id_kelas']) ? 'selected' : ''; ?>><?php echo htmlspecialchars(kk_label($kl['nama_kelas'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if ($asal_row): ?>
                    <form method="POST" id="formNaik" class="mt-3">
                        <input type="hidden" name="id_kelas_asal" value="<?php echo $asal_row['id_kelas']; ?>">
                        <?php if ($tujuan_row): ?>
                            <input type="hidden" name="id_kelas_tujuan" value="<?php echo $tujuan_row['id_kelas']; ?>">
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-datatable" width="100%" cellspacing="0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%"><input type="checkbox" id="selectAllAsal"></th>
                                        <th width="7%">No</th>
                                        <th>NISN</th>
                                        <th>Nama</th>
                                        <th width="10%">L/P</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $qa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas='" . (int)$asal_row['id_kelas'] . "' ORDER BY nama_siswa ASC");
                                    $no = 1;
                                    while ($s = mysqli_fetch_assoc($qa)):
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" class="check-asal" name="ids[]" value="<?php echo $s['id_siswa']; ?>"></td>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($s['nisn']); ?></td>
                                        <td><span class="text-primary"><?php echo htmlspecialchars($s['nama_siswa']); ?></span></td>
                                        <td><?php echo htmlspecialchars($s['jk']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="wrapBtnNaik" class="mt-3" style="display:none;">
                            <button type="button" class="btn btn-success w-100 fw-bold" onclick="confirmNaik()">
                                <i class="fas fa-arrow-up me-1"></i> Proses Naik Kelas
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-light border text-center text-muted mt-3 mb-0">Pilih kelas asal untuk menampilkan siswa.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success"><?php echo htmlspecialchars($ta_tujuan); ?> Tahun Ajaran Tujuan</h6>
                </div>
                <div class="card-body">
                    <label class="form-label fw-semibold small">Kelas</label>
                    <input type="text" class="form-control" value="<?php echo $tujuan_row ? htmlspecialchars(kk_label($tujuan_row['nama_kelas'])) : '-'; ?>" disabled>
                    <small class="text-muted">Kelas tujuan otomatis berdasarkan kelas asal yang dipilih</small>
                    <?php if ($asal_row && $tujuan_row):
                        $qt = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas='" . (int)$tujuan_row['id_kelas'] . "' ORDER BY nama_siswa ASC");
                        $rows_t = [];
                        while ($r = mysqli_fetch_assoc($qt)) $rows_t[] = $r;
                        $qk_log = mysqli_query($koneksi, "SELECT id_siswa FROM kenaikan_log WHERE dari_kelas='" . (int)$asal_row['id_kelas'] . "' AND ke_kelas='" . (int)$tujuan_row['id_kelas'] . "'");
                        $log_ids = [];
                        while ($lr = mysqli_fetch_assoc($qk_log)) $log_ids[(int)$lr['id_siswa']] = true;
                    ?>
                        <div class="alert alert-info mt-3 mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Info:</strong> Kelas tujuan memiliki <?php echo count($rows_t); ?> siswa. Siswa yang akan naik akan ditambahkan ke kelas ini.
                        </div>
                        <form method="POST" id="formBatal">
                            <input type="hidden" name="id_kelas_asal" value="<?php echo $asal_row['id_kelas']; ?>">
                            <input type="hidden" name="id_kelas_tujuan" value="<?php echo $tujuan_row['id_kelas']; ?>">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-datatable" width="100%" cellspacing="0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="5%"><input type="checkbox" id="selectAllTujuan"></th>
                                            <th width="7%">No</th>
                                            <th>NISN</th>
                                            <th>Nama</th>
                                            <th width="10%">L/P</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $nt = 1; foreach ($rows_t as $s):
                                            $is_baru = isset($log_ids[(int)$s['id_siswa']]);
                                        ?>
                                        <tr <?php echo $is_baru ? 'class="table-success"' : ''; ?>>
                                            <td><input type="checkbox" class="check-tujuan" name="batal_ids[]" value="<?php echo $s['id_siswa']; ?>"></td>
                                            <td><?php echo $nt++; ?><?php echo $is_baru ? ' <span class="badge bg-success">Baru naik</span>' : ''; ?></td>
                                            <td><?php echo htmlspecialchars($s['nisn']); ?></td>
                                            <td><span class="text-primary"><?php echo htmlspecialchars($s['nama_siswa']); ?></span></td>
                                            <td><?php echo htmlspecialchars($s['jk']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div id="wrapBtnBatal" class="mt-3" style="display:none;">
                                <button type="button" class="btn btn-outline-danger w-100 fw-bold" onclick="confirmBatal()">
                                    <i class="fas fa-undo me-1"></i> Batal Naik (Kembalikan ke Kelas Asal)
                                </button>
                            </div>
                        </form>
                    <?php elseif ($asal_row && !$tujuan_row): ?>
                        <div class="alert alert-warning mt-3 mb-0">Kelas tertinggi, tidak ada kelas tujuan (lulus).</div>
                    <?php else: ?>
                        <div class="alert alert-light border text-center text-muted mt-3 mb-0">Kelas tujuan akan muncul otomatis setelah kelas asal dipilih.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
function toggleBtnNaik() {
    var n = $('.check-asal:checked').length;
    $('#wrapBtnNaik').toggle(n > 0);
}
$(document).on('change', '#selectAllAsal', function() {
    $('.check-asal').prop('checked', this.checked);
    toggleBtnNaik();
});
$(document).on('change', '.check-asal', function() {
    toggleBtnNaik();
});
$(document).on('change', '#selectAllTujuan', function() {
    $('.check-tujuan').prop('checked', this.checked);
    toggleBtnBatal();
});
$(document).on('change', '.check-tujuan', function() {
    toggleBtnBatal();
});
function toggleBtnBatal() {
    var n = $('.check-tujuan:checked').length;
    $('#wrapBtnBatal').toggle(n > 0);
}
function confirmBatal() {
    var n = $('.check-tujuan:checked').length;
    if (n === 0) {
        Swal.fire('Pilih siswa', 'Centang minimal 1 siswa di kelas tujuan.', 'warning');
        return;
    }
    Swal.fire({
        title: 'Batalkan kenaikan ' + n + ' siswa?',
        text: 'Siswa terpilih dikembalikan ke kelas asal.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Kembalikan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            var form = document.getElementById('formBatal');
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'batal_naik';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        }
    });
}
function confirmNaik() {
    var n = $('.check-asal:checked').length;
    if (n === 0) {
        Swal.fire('Pilih siswa', 'Centang minimal 1 siswa.', 'warning');
        return;
    }
    Swal.fire({
        title: 'Naikkan ' + n + ' siswa?',
        text: 'Siswa terpilih dipindah ke kelas tujuan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Naikkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            var form = document.getElementById('formNaik');
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'proses_naik';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        }
    });
}
</script>
