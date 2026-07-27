<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

include '../../includes/init_session.php';

try {
    include '../../config/database.php';

    if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
        throw new Exception('Akses ditolak');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak valid');
    }

    ob_clean();
    header('Content-Type: application/json');

    set_time_limit(300);
    $root = dirname(__DIR__, 2);

    // URL download ZIP dari repo GitHub
    $zip_url = 'https://github.com/dewecorp/cbt/archive/refs/heads/main.zip';

    // File/folder yang dilindungi (tidak ditimpa)
    $protected = [
        'config/database.php',
        '.htaccess',
        'sessions/',
        'assets/uploads/',
        'assets/img/siswa/',
        'assets/img/guru/',
    ];

    // Ekstensi file yang diizinkan
    $allowed_ext = ['php', 'js', 'css', 'html', 'json', 'md', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'map', 'xml', 'yml', 'yaml', 'dist'];

    // Download ZIP
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zip_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CBT-Update/1.0');
    $zip_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($zip_data === false || $http_code !== 200) {
        throw new Exception('Gagal download update: ' . ($curl_err ?: "HTTP $http_code"));
    }

    // Simpan ZIP ke temp
    $tmp_dir = $root . '/temp_update';
    if (!is_dir($tmp_dir)) {
        @mkdir($tmp_dir, 0777, true);
    }
    $zip_file = $tmp_dir . '/update.zip';
    file_put_contents($zip_file, $zip_data);

    // Ekstrak ZIP
    $zip = new ZipArchive();
    if ($zip->open($zip_file) !== true) {
        @unlink($zip_file);
        throw new Exception('Gagal membuka file ZIP');
    }

    $extract_dir = $tmp_dir . '/extracted';
    if (is_dir($extract_dir)) {
        // Hapus folder beserta isinya
        $it = new RecursiveDirectoryIterator($extract_dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) {
            if ($f->isDir()) { @rmdir($f->getRealPath()); }
            else { @unlink($f->getRealPath()); }
        }
        @rmdir($extract_dir);
    }
    @mkdir($extract_dir, 0777, true);
    $zip->extractTo($extract_dir);
    $zip->close();
    @unlink($zip_file);

    // Cari folder hasil extract (biasanya cbt-main/)
    $items = scandir($extract_dir);
    $source_dir = null;
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($extract_dir . '/' . $item)) {
            $source_dir = $extract_dir . '/' . $item;
            break;
        }
    }
    if (!$source_dir) {
        // Hapus temp
        $it = new RecursiveDirectoryIterator($extract_dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) {
            if ($f->isDir()) { @rmdir($f->getRealPath()); }
            else { @unlink($f->getRealPath()); }
        }
        @rmdir($extract_dir);
        @rmdir($tmp_dir);
        throw new Exception('Folder utama tidak ditemukan dalam ZIP');
    }

    // Copy file dari source ke root, skip protected
    $copied = 0;
    $skipped = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($it as $file) {
        $relative = substr($file->getPathname(), strlen($source_dir) + 1);
        $relative = str_replace('\\', '/', $relative);

        // Cek protected
        $is_protected = false;
        foreach ($protected as $p) {
            if ($relative === $p || strpos($relative, $p) === 0) {
                $is_protected = true;
                break;
            }
        }
        if ($is_protected) {
            $skipped++;
            continue;
        }

        // Cek extension
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            $skipped++;
            continue;
        }

        $dest = $root . '/' . $relative;

        if ($file->isDir()) {
            if (!is_dir($dest)) {
                @mkdir($dest, 0755, true);
            }
        } else {
            // Baca konten, scan pola berbahaya
            $content = file_get_contents($file->getPathname());
            $dangerous = ['\beval\b', '\bexec\b', '\bshell_exec\b', '\bsystem\b', '\bpopen\b', '\bpassthru\b', '\bpcntl_exec\b', '\bassert\b', '\bcreate_function\b', '\bobfuscator\b', '\bc99shell\b', '\br57shell\b', '\bwebshell\b', '\bbackdoor\b'];
            $safe = true;
            foreach ($dangerous as $pattern) {
                if (preg_match('/' . $pattern . '/i', $content)) {
                    $path_check = str_replace('\\', '/', $relative);
                    $allowed_in = ['modules/backup/action.php', 'modules/update/'];
                    $is_allowed = false;
                    foreach ($allowed_in as $a) {
                        if (strpos($path_check, $a) !== false) {
                            $is_allowed = true;
                            break;
                        }
                    }
                    if (!$is_allowed) {
                        $safe = false;
                        break;
                    }
                }
            }
            if (!$safe) {
                $skipped++;
                continue;
            }
            if ($ext === 'php') {
                $check = @shell_exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1');
                if ($check && strpos($check, 'No syntax errors') === false && strpos($check, 'Syntax error') !== false) {
                    $skipped++;
                    continue;
                }
            }
            file_put_contents($dest, $content);
            $copied++;
        }
    }

    // Hapus temp
    $it = new RecursiveDirectoryIterator($extract_dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $f) {
        if ($f->isDir()) { @rmdir($f->getRealPath()); }
        else { @unlink($f->getRealPath()); }
    }
    @rmdir($extract_dir);
    @rmdir($tmp_dir);

    if (function_exists('log_activity')) {
        log_activity('update', 'system', 'Update sistem: ' . $copied . ' file diperbarui, ' . $skipped . ' dilewati');
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Update selesai. $copied file diperbarui, $skipped file dilewati (lindungi)."
    ]);

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
ob_end_flush();
