<?php
include '../../config/database.php';
$page_title = 'Integrasi API';
include '../../includes/header.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    echo "<script>window.location='../../dashboard.php';</script>";
    exit;
}

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS simad_endpoint (
    key_name VARCHAR(32) NOT NULL,
    url TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function simad_ep_origin($url) {
    $p = parse_url(trim((string)$url));
    if (!$p || empty($p['scheme']) || empty($p['host'])) return '-';
    $port = isset($p['port']) ? ':' . (int)$p['port'] : '';
    return $p['scheme'] . '://' . $p['host'] . $port;
}

function simad_ep_test($url) {
    $url = trim((string)$url);
    if ($url === '') return ['ok' => false, 'error' => 'Endpoint kosong'];
    if (!preg_match('#^https?://#i', $url)) return ['ok' => false, 'error' => 'URL harus diawali http:// atau https://'];
    if (!function_exists('curl_init')) return ['ok' => false, 'error' => 'cURL tidak tersedia di server'];
    $t0 = microtime(true);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CBT-Sync/1.0 (endpoint-test)');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $ms = round((microtime(true) - $t0) * 1000);

    if ($response === false) return ['ok' => false, 'error' => $curlErr ? $curlErr : 'Gagal menghubungi endpoint', 'ms' => $ms, 'http' => $httpCode];
    if ($httpCode < 200 || $httpCode >= 300) return ['ok' => false, 'error' => 'HTTP ' . $httpCode, 'ms' => $ms, 'http' => $httpCode];
    $json = json_decode($response, true);
    if (!is_array($json)) return ['ok' => false, 'error' => 'Respon bukan JSON valid', 'ms' => $ms, 'http' => $httpCode];
    if (!isset($json['status']) || $json['status'] !== 'success') {
        $msg = isset($json['message']) ? (string)$json['message'] : 'Status tidak success';
        return ['ok' => false, 'error' => $msg, 'ms' => $ms, 'http' => $httpCode];
    }
    $n = isset($json['data']) && is_array($json['data']) ? count($json['data']) : 0;
    return ['ok' => true, 'http' => $httpCode, 'ms' => $ms, 'count' => $n];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $ep_siswa = isset($_POST['endpoint_siswa']) ? trim($_POST['endpoint_siswa']) : '';
    $ep_guru = isset($_POST['endpoint_guru']) ? trim($_POST['endpoint_guru']) : '';
    $err = '';
    foreach (['siswa' => $ep_siswa, 'guru' => $ep_guru] as $k => $v) {
        if ($v !== '' && !preg_match('#^https?://#i', $v)) $err = 'Endpoint ' . $k . ' harus diawali http:// atau https://';
    }
    if ($err !== '') {
        echo "<script>Swal.fire('Gagal', '$err', 'error');</script>";
    } else {
        $es = mysqli_real_escape_string($koneksi, $ep_siswa);
        $eg = mysqli_real_escape_string($koneksi, $ep_guru);
        mysqli_query($koneksi, "REPLACE INTO simad_endpoint (key_name, url) VALUES ('siswa', '$es'), ('guru', '$eg')");
        log_activity('update', 'integrasi_api', 'update endpoint SIMAD siswa+guru');
        echo "<script>
            Swal.fire({icon:'success', title:'Berhasil', text:'Endpoint berhasil disimpan. Sync guru/siswa langsung pakai endpoint baru.', timer:2000, showConfirmButton:false})
            .then(() => { window.location.href = 'index.php'; });
        </script>";
    }
}

$test_result = null;
$test_type = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tes'])) {
    $test_type = isset($_POST['tipe']) ? $_POST['tipe'] : 'siswa';
    $test_type = ($test_type === 'guru') ? 'guru' : 'siswa';
    $url = ($test_type === 'guru')
        ? (isset($_POST['endpoint_guru']) ? trim($_POST['endpoint_guru']) : '')
        : (isset($_POST['endpoint_siswa']) ? trim($_POST['endpoint_siswa']) : '');
    if ($url === '') {
        $q = mysqli_query($koneksi, "SELECT url FROM simad_endpoint WHERE key_name='$test_type' LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $r = mysqli_fetch_assoc($q);
            $url = trim((string)$r['url']);
        }
    }
    $test_result = simad_ep_test($url);
    $test_result['url'] = $url;
}

$ep = ['siswa' => '', 'guru' => '', 'siswa_at' => '-', 'guru_at' => '-'];
$q = mysqli_query($koneksi, "SELECT key_name, url, updated_at FROM simad_endpoint WHERE key_name IN ('siswa','guru')");
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        if ($r['key_name'] === 'siswa') { $ep['siswa'] = $r['url']; $ep['siswa_at'] = $r['updated_at']; }
        if ($r['key_name'] === 'guru') { $ep['guru'] = $r['url']; $ep['guru_at'] = $r['updated_at']; }
    }
}
$ep['siswa'] = trim((string)$ep['siswa']);
$ep['guru'] = trim((string)$ep['guru']);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 mb-0">Integrasi API</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="../../admin.php?role=admin" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item">System</li>
                <li class="breadcrumb-item active">Integrasi API</li>
            </ol>
        </nav>
    </div>

    <?php if ($test_result): ?>
        <?php if ($test_result['ok']): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-1"></i>
                <strong>Tes <?php echo htmlspecialchars($test_type); ?> berhasil.</strong>
                HTTP <?php echo (int)$test_result['http']; ?> &middot; <?php echo (int)$test_result['ms']; ?> ms &middot; data: <?php echo (int)$test_result['count']; ?> baris.
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-times-circle me-1"></i>
                <strong>Tes <?php echo htmlspecialchars($test_type); ?> gagal:</strong>
                <?php echo htmlspecialchars($test_result['error']); ?>
                <?php if (isset($test_result['http'])): ?> (HTTP <?php echo (int)$test_result['http']; ?>)<?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-plug me-1"></i> Endpoint SIMAD</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                Copy-paste endpoint dari SIMAD ke kolom bawah lalu <strong>Simpan</strong>. Sync Data Guru / Data Siswa langsung pakai endpoint baru tanpa bongkar backend. Domain boleh ganti bebas.
            </div>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Endpoint Siswa</label>
                    <input type="text" class="form-control" name="endpoint_siswa" value="<?php echo htmlspecialchars($ep['siswa']); ?>" placeholder="https://domain-baru/.../students.php?api_key=..." autocomplete="off">
                    <small class="text-muted d-block mt-1">
                        Domain: <strong><?php echo htmlspecialchars(simad_ep_origin($ep['siswa'])); ?></strong> &middot; update: <?php echo htmlspecialchars($ep['siswa_at']); ?>
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Endpoint Guru</label>
                    <input type="text" class="form-control" name="endpoint_guru" value="<?php echo htmlspecialchars($ep['guru']); ?>" placeholder="https://domain-baru/.../teachers.php?api_key=..." autocomplete="off">
                    <small class="text-muted d-block mt-1">
                        Domain: <strong><?php echo htmlspecialchars(simad_ep_origin($ep['guru'])); ?></strong> &middot; update: <?php echo htmlspecialchars($ep['guru_at']); ?>
                    </small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" name="simpan" value="1" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Simpan Endpoint
                    </button>
                    <button type="submit" name="tes" value="1" class="btn btn-outline-primary" onclick="document.getElementById('tipeTes').value='siswa';">
                        <i class="fas fa-bolt me-1"></i> Tes Siswa
                    </button>
                    <button type="submit" name="tes" value="1" class="btn btn-outline-secondary" onclick="document.getElementById('tipeTes').value='guru';">
                        <i class="fas fa-bolt me-1"></i> Tes Guru
                    </button>
                    <input type="hidden" name="tipe" id="tipeTes" value="siswa">
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Dipakai di mana</h6>
        </div>
        <div class="card-body small text-muted">
            Endpoint Siswa dipakai tombol <strong>Sinkron SIMAD</strong> di Data Siswa.<br>
            Endpoint Guru dipakai tombol <strong>Sinkron SIMAD</strong> di Data Guru.<br>
            Ganti domain cukup dari halaman ini.
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
