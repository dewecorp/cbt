<?php
require '../../vendor/autoload.php';
include '../../config/database.php';
$page_title = 'Data Guru';
include '../../includes/header.php';

use Shuchkin\SimpleXLSX;

// Fetch Kelas
$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$kelas_opts = [];
while($k = mysqli_fetch_assoc($q_kelas)) {
    $kelas_opts[] = $k;
}

// Fetch Mapel
$q_mapel = mysqli_query($koneksi, "SELECT * FROM mapel ORDER BY nama_mapel ASC");
$mapel_opts = [];
while($m = mysqli_fetch_assoc($q_mapel)) {
    $mapel_opts[] = $m;
}

function simad_guru_normalize_gender($value) {
    $v = strtolower(trim((string)$value));
    if ($v === 'l' || $v === 'laki-laki' || $v === 'laki laki' || $v === 'laki' || $v === 'male' || $v === 'm') {
        return 'L';
    }
    if ($v === 'p' || $v === 'perempuan' || $v === 'female' || $v === 'f') {
        return 'P';
    }
    return '';
}

function simad_guru_normalize_kelas_key($value) {
    $raw = strtoupper(trim((string)$value));
    if ($raw === '') {
        return '';
    }
    $raw = preg_replace('/\s+/', ' ', $raw);
    $raw = preg_replace('/\bKELAS\b/i', '', $raw);
    $raw = trim($raw);

    if (preg_match('/(\d{1,2})\s*([A-Z])?/i', $raw, $m)) {
        $num = (int)$m[1];
        $letter = isset($m[2]) ? strtoupper(trim($m[2])) : '';
        return (string)$num . $letter;
    }

    $romanMap = [
        'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6,
        'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12,
    ];
    if (preg_match('/\b(XII|XI|X|IX|VIII|VII|VI|V|IV|III|II|I)\b\s*([A-Z])?/i', $raw, $m)) {
        $roman = strtoupper($m[1]);
        $num = isset($romanMap[$roman]) ? $romanMap[$roman] : 0;
        $letter = isset($m[2]) ? strtoupper(trim($m[2])) : '';
        if ($num > 0) {
            return (string)$num . $letter;
        }
    }

    $raw = preg_replace('/[^A-Z0-9]/', '', $raw);
    return $raw;
}

function simad_guru_kelas_id_by_name($kelas_opts, $nama_kelas) {
    $nama_kelas = trim(preg_replace('/\s+/', ' ', (string)$nama_kelas));
    if ($nama_kelas === '') {
        return 0;
    }
    foreach ($kelas_opts as $k) {
        if (strcasecmp(trim((string)$k['nama_kelas']), $nama_kelas) === 0) {
            return (int)$k['id_kelas'];
        }
    }
    $key = simad_guru_normalize_kelas_key($nama_kelas);
    if ($key === '') {
        return 0;
    }
    foreach ($kelas_opts as $k) {
        if (simad_guru_normalize_kelas_key($k['nama_kelas']) === $key) {
            return (int)$k['id_kelas'];
        }
    }
    return 0;
}

/** Hanya kelas (wali + mengajar_list); mapel tidak dipakai sinkron agar data lokal CBT tidak tertimpa. */
function simad_guru_collect_kelas_csv_from_api_row($teacher, $kelas_opts) {
    $kelas_ids = [];

    $wali_csv = isset($teacher['kelas_wali']) ? trim((string)$teacher['kelas_wali']) : '';
    if ($wali_csv !== '') {
        foreach (preg_split('/\s*,\s*/', $wali_csv) as $nm) {
            $id = simad_guru_kelas_id_by_name($kelas_opts, $nm);
            if ($id > 0) {
                $kelas_ids[$id] = true;
            }
        }
    }

    $list = isset($teacher['mengajar_list']) ? $teacher['mengajar_list'] : null;
    if (is_array($list)) {
        foreach ($list as $item) {
            if (is_string($item)) {
                $idk = simad_guru_kelas_id_by_name($kelas_opts, $item);
                if ($idk > 0) {
                    $kelas_ids[$idk] = true;
                }
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $nk = '';
            foreach (['nama_kelas', 'kelas', 'nama_kelas_wali', 'class', 'kelas_nama'] as $ck) {
                if (!empty($item[$ck])) {
                    $nk = trim((string)$item[$ck]);
                    break;
                }
            }
            if ($nk !== '') {
                $idk = simad_guru_kelas_id_by_name($kelas_opts, $nk);
                if ($idk > 0) {
                    $kelas_ids[$idk] = true;
                }
            }
        }
    }

    return implode(',', array_keys($kelas_ids));
}

function simad_guru_random_password($length = 8) {
    return substr(str_shuffle('0123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz'), 0, $length);
}

function simad_guru_api_origin() {
    $u = defined('SIMAD_TEACHER_API_URL') ? SIMAD_TEACHER_API_URL : '';
    $p = parse_url($u);
    if (!$p || empty($p['scheme']) || empty($p['host'])) {
        return '';
    }
    $port = isset($p['port']) ? ':' . (int)$p['port'] : '';
    return $p['scheme'] . '://' . $p['host'] . $port;
}

function simad_guru_try_save_foto($username_base, $foto_raw) {
    $foto_raw = trim((string)$foto_raw);
    if ($foto_raw === '') {
        return '';
    }
    $origin = simad_guru_api_origin();
    $url = '';
    if (preg_match('#^https?://#i', $foto_raw)) {
        $url = $foto_raw;
    } elseif ($origin !== '' && isset($foto_raw[0]) && $foto_raw[0] === '/') {
        $url = $origin . $foto_raw;
    } elseif ($origin !== '') {
        $url = rtrim($origin, '/') . '/' . ltrim($foto_raw, '/');
    }
    if ($url === '' || !function_exists('curl_init')) {
        return '';
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CBT-Sync/1.0');
    $bin = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    if ($bin === false || $code < 200 || $code >= 300 || strlen($bin) < 32) {
        return '';
    }
    $ext = 'jpg';
    if (is_string($ctype)) {
        if (stripos($ctype, 'png') !== false) {
            $ext = 'png';
        } elseif (stripos($ctype, 'jpeg') !== false || stripos($ctype, 'jpg') !== false) {
            $ext = 'jpg';
        }
    }
    $path = parse_url($url, PHP_URL_PATH);
    if (is_string($path) && preg_match('/\.(jpe?g|png)$/i', $path, $mm)) {
        $ext = strtolower($mm[1]) === 'jpeg' ? 'jpg' : strtolower($mm[1]);
    }
    $safe_user = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username_base);
    $target_dir = __DIR__ . '/../../assets/img/guru/';
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    $fname = $safe_user . '_simad_' . time() . '.' . $ext;
    $full = $target_dir . $fname;
    if (@file_put_contents($full, $bin) === false) {
        return '';
    }
    return $fname;
}

function simad_guru_build_api_url($updated_since, $limit) {
    $base = defined('SIMAD_TEACHER_API_URL') ? SIMAD_TEACHER_API_URL : '';
    if ($base === '') {
        return '';
    }
    $q = [];
    if ($updated_since !== '') {
        $q['updated_since'] = $updated_since;
    }
    if ($limit > 0) {
        $q['limit'] = $limit;
    }
    if ($q === []) {
        return $base;
    }
    $sep = strpos($base, '?') !== false ? '&' : '?';
    return $base . $sep . http_build_query($q);
}

function simad_guru_fetch($apiUrl) {
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'cURL tidak tersedia di server'];
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CBT-Sync/1.0 (+https://simad.misultanfattah.sch.id)');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
    ]);
    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => $curlErr ? $curlErr : 'Gagal mengambil data guru dari SIMAD'];
    }
    $json = json_decode($response, true);
    if (!is_array($json)) {
        if ($httpCode < 200 || $httpCode >= 300) {
            return ['ok' => false, 'error' => 'HTTP ' . $httpCode . ' dari SIMAD'];
        }
        return ['ok' => false, 'error' => 'Respon SIMAD bukan JSON valid'];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        $msg = isset($json['message']) ? (string)$json['message'] : ('HTTP ' . $httpCode);
        return ['ok' => false, 'error' => $msg, 'http' => $httpCode];
    }
    if (!isset($json['status']) || $json['status'] !== 'success') {
        $msg = isset($json['message']) ? (string)$json['message'] : 'Status SIMAD tidak sukses';
        return ['ok' => false, 'error' => $msg];
    }
    $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];
    $last_sync = isset($json['last_sync']) ? trim((string)$json['last_sync']) : date('Y-m-d H:i:s');
    $sync_mode = isset($json['sync_mode']) ? (string)$json['sync_mode'] : 'full';
    return ['ok' => true, 'data' => $data, 'last_sync' => $last_sync, 'sync_mode' => $sync_mode];
}

function simad_guru_resolve_login_username($row) {
    $nuptk = isset($row['nuptk']) ? trim((string)$row['nuptk']) : '';
    if ($nuptk !== '') {
        return $nuptk;
    }
    $kode = isset($row['kode_guru']) ? trim((string)$row['kode_guru']) : '';
    if ($kode !== '') {
        return $kode;
    }
    return '';
}

// Sinkron guru dari SIMAD (admin)
if (isset($_POST['sync_simad_guru'])) {
    if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
        echo "<script>
            Swal.fire({icon:'error', title:'Akses Ditolak', text:'Fitur sinkronisasi hanya untuk admin.'})
            .then(() => { window.location.href = 'guru.php'; });
        </script>";
    } else {
        $apiBase = defined('SIMAD_TEACHER_API_URL') ? SIMAD_TEACHER_API_URL : '';
        if ($apiBase === '') {
            echo "<script>
                Swal.fire({icon:'error', title:'Konfigurasi', text:'SIMAD_TEACHER_API_URL belum dikonfigurasi.'})
                .then(() => { window.location.href = 'guru.php'; });
            </script>";
        } else {
            $cursor_since = '';
            $qc = mysqli_query($koneksi, "SELECT cursor_since FROM simad_sync_cursor WHERE sync_type='guru' LIMIT 1");
            if ($qc && mysqli_num_rows($qc) > 0) {
                $rc = mysqli_fetch_assoc($qc);
                $cursor_since = trim((string)($rc['cursor_since'] ?? ''));
            }

            $try_incremental = $cursor_since !== '';
            $url = simad_guru_build_api_url($try_incremental ? $cursor_since : '', 1000);
            $res = simad_guru_fetch($url);

            if (!$res['ok'] && $try_incremental && isset($res['http']) && (int)$res['http'] === 400) {
                $errLower = strtolower($res['error']);
                if (strpos($errLower, 'updated_since') !== false || strpos($errLower, 'updated_at') !== false) {
                    $url = simad_guru_build_api_url('', 1000);
                    $res = simad_guru_fetch($url);
                }
            }

            if (!$res['ok']) {
                $err = addslashes($res['error']);
                echo "<script>
                    Swal.fire({icon:'error', title:'Gagal Sinkron', text:'$err'})
                    .then(() => { window.location.href = 'guru.php'; });
                </script>";
            } else {
                $inserted = 0;
                $updated = 0;
                $skipped_invalid = 0;
                $skipped_username_conflict = 0;
                $errors = 0;

                foreach ($res['data'] as $t) {
                    if (!is_array($t)) {
                        $skipped_invalid++;
                        continue;
                    }
                    $login = simad_guru_resolve_login_username($t);
                    if ($login === '') {
                        $skipped_invalid++;
                        continue;
                    }
                    if (strlen($login) > 50) {
                        $login = substr($login, 0, 50);
                    }

                    $nama_raw = isset($t['nama_guru']) ? trim((string)$t['nama_guru']) : '';
                    if ($nama_raw === '') {
                        $skipped_invalid++;
                        continue;
                    }

                    $jk = simad_guru_normalize_gender(isset($t['jenis_kelamin']) ? $t['jenis_kelamin'] : '');
                    $jk_sql_val = ($jk === 'L' || $jk === 'P') ? "'" . mysqli_real_escape_string($koneksi, $jk) . "'" : 'NULL';

                    $mengajar_kelas = simad_guru_collect_kelas_csv_from_api_row($t, $kelas_opts);

                    $id_simad = isset($t['id_guru']) ? (int)$t['id_guru'] : 0;
                    $u_esc = mysqli_real_escape_string($koneksi, $login);
                    $nama_esc = mysqli_real_escape_string($koneksi, $nama_raw);

                    $loc = null;
                    if ($id_simad > 0) {
                        $qby_simad = mysqli_query($koneksi, "SELECT id_user, level, username, foto FROM users WHERE level='guru' AND simad_id_guru='" . $id_simad . "' LIMIT 1");
                        if ($qby_simad && mysqli_num_rows($qby_simad) > 0) {
                            $loc = mysqli_fetch_assoc($qby_simad);
                        }
                    }
                    if ($loc === null) {
                        $qexist = mysqli_query($koneksi, "SELECT id_user, level, username, foto FROM users WHERE username='$u_esc' LIMIT 1");
                        if (!$qexist) {
                            $errors++;
                            continue;
                        }
                        if (mysqli_num_rows($qexist) > 0) {
                            $loc = mysqli_fetch_assoc($qexist);
                        }
                    }

                    $foto_new = '';
                    if (!empty($t['foto'])) {
                        $foto_new = simad_guru_try_save_foto($login, $t['foto']);
                    }

                    if ($loc === null) {
                        $pass = simad_guru_random_password(8);
                        $pass_h = password_hash($pass, PASSWORD_DEFAULT);
                        $pass_e = mysqli_real_escape_string($koneksi, $pass);
                        $pass_h_e = mysqli_real_escape_string($koneksi, $pass_h);
                        $mk_e = mysqli_real_escape_string($koneksi, $mengajar_kelas);
                        $foto_sql = $foto_new !== '' ? "'" . mysqli_real_escape_string($koneksi, $foto_new) . "'" : "''";
                        $simad_col = $id_simad > 0 ? ", simad_id_guru" : '';
                        $simad_val = $id_simad > 0 ? ", " . $id_simad : '';
                        $sql = "INSERT INTO users (username, password, password_plain, nama_lengkap, jk, foto, level, mengajar_kelas, mengajar_mapel$simad_col)
                                VALUES ('$u_esc', '$pass_h_e', '$pass_e', '$nama_esc', $jk_sql_val, $foto_sql, 'guru', '$mk_e', ''$simad_val)";
                        if (mysqli_query($koneksi, $sql)) {
                            $inserted++;
                        } else {
                            $errors++;
                        }
                    } else {
                        if (($loc['level'] ?? '') !== 'guru') {
                            $skipped_username_conflict++;
                            continue;
                        }
                        $id_user = (int)$loc['id_user'];
                        $mk_e = mysqli_real_escape_string($koneksi, $mengajar_kelas);

                        $foto_keep = $loc['foto'] ?? '';
                        $img_dir = __DIR__ . '/../../assets/img/guru/';
                        if (array_key_exists('foto', $t)) {
                            $simad_foto = trim((string)$t['foto']);
                            if ($simad_foto === '') {
                                if ($foto_keep !== '' && is_file($img_dir . $foto_keep)) {
                                    @unlink($img_dir . $foto_keep);
                                }
                                $foto_keep = '';
                            } elseif ($foto_new !== '') {
                                if ($foto_keep !== '' && is_file($img_dir . $foto_keep)) {
                                    @unlink($img_dir . $foto_keep);
                                }
                                $foto_keep = $foto_new;
                            }
                        } elseif ($foto_new !== '') {
                            if ($foto_keep !== '' && is_file($img_dir . $foto_keep)) {
                                @unlink($img_dir . $foto_keep);
                            }
                            $foto_keep = $foto_new;
                        }
                        $foto_e = mysqli_real_escape_string($koneksi, $foto_keep);

                        $simad_sql = $id_simad > 0 ? ", simad_id_guru='" . $id_simad . "'" : '';
                        $username_fix_sql = '';
                        $cur_username = isset($loc['username']) ? (string)$loc['username'] : '';
                        if ($cur_username !== $login) {
                            $q_other = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$u_esc' AND id_user <> '" . $id_user . "' LIMIT 1");
                            if ($q_other && mysqli_num_rows($q_other) === 0) {
                                $username_fix_sql = ", username='$u_esc'";
                            }
                        }
                        // SIMAD: nama, NUPTK/username, jk, mengajar_kelas, foto. Password & mengajar_mapel tidak diubah.
                        $upd = "UPDATE users SET nama_lengkap='$nama_esc', jk=$jk_sql_val, foto='$foto_e', mengajar_kelas='$mk_e'$simad_sql$username_fix_sql WHERE id_user='$id_user'";
                        if (mysqli_query($koneksi, $upd)) {
                            $updated++;
                        } else {
                            $errors++;
                        }
                    }
                }

                $last_sync = $res['last_sync'];
                if ($last_sync !== '') {
                    $ls_esc = mysqli_real_escape_string($koneksi, $last_sync);
                    mysqli_query($koneksi, "INSERT INTO simad_sync_cursor (sync_type, cursor_since) VALUES ('guru', '$ls_esc')
                        ON DUPLICATE KEY UPDATE cursor_since='$ls_esc'");
                }

                log_activity('sync', 'guru', 'SIMAD mode ' . ($res['sync_mode'] ?? '') . ' baru ' . $inserted . ', perbarui ' . $updated . ', skip_invalid ' . $skipped_invalid . ', skip_bukan_guru ' . $skipped_username_conflict . ', err ' . $errors);

                $msg = 'Guru baru ditambahkan: ' . $inserted . ', Guru diperbarui: ' . $updated . ', Lewati (data tidak valid): ' . $skipped_invalid . ', Lewati (username sudah dipakai non-guru): ' . $skipped_username_conflict . ', Error: ' . $errors;
                $msg = addslashes($msg);
                echo "<script>
                    Swal.fire({icon:'success', title:'Sinkron SIMAD Selesai', text:'$msg'})
                    .then(() => { window.location.href = 'guru.php'; });
                </script>";
            }
        }
    }
}

// Handle Add
if (isset($_POST['add'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $jk = mysqli_real_escape_string($koneksi, $_POST['jk']);
    $password_plain = $_POST['password'];
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    
    // Handle Multiselect
    $mengajar_kelas = isset($_POST['mengajar_kelas']) ? implode(',', $_POST['mengajar_kelas']) : '';
    $mengajar_mapel = isset($_POST['mengajar_mapel']) ? implode(',', $_POST['mengajar_mapel']) : '';
    
    // Upload Foto
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "../../assets/img/guru/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $foto_name = $username . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $foto_name;
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                $foto = $foto_name;
            }
        }
    }
    
    $check = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    if(mysqli_num_rows($check) > 0) {
         echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'NUPTK sudah digunakan!',
            });
        </script>";
    } else {
        $query = "INSERT INTO users (username, password, password_plain, nama_lengkap, jk, foto, level, mengajar_kelas, mengajar_mapel) VALUES ('$username', '$password_hash', '$password_plain', '$nama_lengkap', '$jk', '$foto', 'guru', '$mengajar_kelas', '$mengajar_mapel')";
        if(mysqli_query($koneksi, $query)) {
            log_activity('create', 'guru', 'tambah guru ' . $username);
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data guru berhasil ditambahkan',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'guru.php?role=admin';
                });
            </script>";
        } else {
            echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
        }
    }
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id_user = $_POST['id_user'];
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $jk = mysqli_real_escape_string($koneksi, $_POST['jk']);
    $password_plain = $_POST['password'];
    
    // Handle Multiselect
    $mengajar_kelas = isset($_POST['mengajar_kelas']) ? implode(',', $_POST['mengajar_kelas']) : '';
    $mengajar_mapel = isset($_POST['mengajar_mapel']) ? implode(',', $_POST['mengajar_mapel']) : '';
    
    // Get old data
    $q_old = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'");
    $d_old = mysqli_fetch_assoc($q_old);
    
    // Upload Foto
    $foto = $d_old['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "../../assets/img/guru/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $foto_name = $username . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $foto_name;
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                // Delete old photo
                if ($d_old['foto'] && file_exists($target_dir . $d_old['foto'])) {
                    unlink($target_dir . $d_old['foto']);
                }
                $foto = $foto_name;
            }
        }
    }
    
    $query_str = "UPDATE users SET username='$username', nama_lengkap='$nama_lengkap', jk='$jk', foto='$foto', mengajar_kelas='$mengajar_kelas', mengajar_mapel='$mengajar_mapel'";
    
    if(!empty($password_plain)) {
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
        $query_str .= ", password='$password_hash', password_plain='$password_plain'";
    }
    
    $query_str .= " WHERE id_user='$id_user'";
    
    if(mysqli_query($koneksi, $query_str)) {
        log_activity('update', 'guru', 'edit guru ' . $username);
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data guru berhasil diperbarui',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'guru.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
    }
}

// Handle Import
if (isset($_POST['import'])) {
    if (isset($_FILES['file_import']) && $_FILES['file_import']['error'] == 0) {
        $ext = pathinfo($_FILES['file_import']['name'], PATHINFO_EXTENSION);
        if ($ext == 'xlsx') {
            if ($xlsx = SimpleXLSX::parse($_FILES['file_import']['tmp_name'])) {
                $success = 0;
                $failed = 0;
                
                // Skip header (first row)
                $rows = $xlsx->rows();
                if (is_array($rows)) {
                    for ($i = 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        // Format Excel: NUPTK, Nama Lengkap, Password
                        if (is_array($row) && count($row) >= 3) {
                            $username = mysqli_real_escape_string($koneksi, $row[0]);
                            $nama = mysqli_real_escape_string($koneksi, $row[1]);
                            $pass = mysqli_real_escape_string($koneksi, $row[2]);
                            
                            // Skip empty rows
                            if(empty($username) || empty($nama)) continue;

                            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
                            
                            // Cek duplicate
                            $check = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
                            if (mysqli_num_rows($check) == 0) {
                                $q = "INSERT INTO users (username, password, password_plain, nama_lengkap, level) VALUES ('$username', '$pass_hash', '$pass', '$nama', 'guru')";
                                if (mysqli_query($koneksi, $q)) {
                                    $success++;
                                } else {
                                    $failed++;
                                }
                            } else {
                                $failed++; // Duplicate
                            }
                        }
                    }
                }
                
                log_activity('import', 'guru', 'import guru berhasil ' . $success . ', gagal ' . $failed);
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Import Selesai',
                        text: 'Berhasil: $success, Gagal/Duplikat: $failed',
                    }).then(() => {
                        window.location.href = 'guru.php';
                    });
                </script>";
            } else {
                echo "<script>Swal.fire('Error', 'Gagal parsing file Excel', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Error', 'Format file harus XLSX', 'error');</script>";
        }
    }
}

// Handle Delete via GET
if (isset($_GET['delete'])) {
    $id_user = $_GET['delete'];
    
    if(mysqli_query($koneksi, "DELETE FROM users WHERE id_user='$id_user'")) {
        log_activity('delete', 'guru', 'hapus guru ' . $id_user);
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data guru berhasil dihapus',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'guru.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
    }
}

// Get Statistics for Guru
$stats_query = mysqli_query($koneksi, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN jk = 'L' THEN 1 ELSE 0 END) as total_l,
    SUM(CASE WHEN jk = 'P' THEN 1 ELSE 0 END) as total_p
    FROM users WHERE level='guru'");
$stats_res = mysqli_fetch_assoc($stats_query);
$total_guru = $stats_res['total'] ?? 0;
$total_l = $stats_res['total_l'] ?? 0;
$total_p = $stats_res['total_p'] ?? 0;
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    /* Custom Select2 Styling for Multiselect */
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice {
        background-color: #0d6efd; /* Primary Color */
        border-color: #0d6efd;
        color: #fff;
        border-radius: 0.35rem;
        padding: 2px 8px;
        font-size: 0.85rem;
    }

    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice .select2-selection__choice__remove {   
        color: #fff;
        margin-right: 5px;
        border-right: 1px solid rgba(255, 255, 255, 0.3);
    }

    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice .select2-selection__choice__remove:hover {
        background-color: transparent;
        color: #fff;
    }

    /* Fix Focus Border */
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Guru</h1>
    <div class="d-flex flex-wrap gap-2">
        <?php if (isset($_SESSION['level']) && $_SESSION['level'] === 'admin'): ?>
        <button type="button" class="btn btn-success" onclick="confirmSyncSimadGuru()">
            <i class="fas fa-sync"></i> Sinkron SIMAD
        </button>
        <?php endif; ?>
        <a href="export_guru_excel.php" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="export_guru_pdf.php" target="_blank" class="btn btn-secondary">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-file-excel"></i> Import Excel
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Tambah Guru
        </button>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-md-12">
                <div class="d-flex flex-wrap gap-2 justify-content-start">
                    <div class="badge bg-primary px-3 py-2 fs-6 fw-normal">
                        <i class="fas fa-users me-1"></i> Total Guru: <?php echo $total_guru; ?>
                    </div>
                    <div class="badge bg-info px-3 py-2 fs-6 fw-normal text-white">
                        <i class="fas fa-mars me-1"></i> Laki-laki: <?php echo $total_l; ?>
                    </div>
                    <div class="badge bg-danger px-3 py-2 fs-6 fw-normal">
                        <i class="fas fa-venus me-1"></i> Perempuan: <?php echo $total_p; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-datatable" width="100%" cellspacing="0">
                <thead class="bg-light">
                    <tr>
                        <th width="5%">No</th>
                        <th width="10%">Foto</th>
                        <th>NUPTK</th>
                        <th>Nama Lengkap</th>
                        <th>L/P</th>
                        <th>Password</th>
                        <th>Mengajar Kelas</th>
                        <th>Mengajar Mapel</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE level='guru' ORDER BY nama_lengkap ASC");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query)) :
                        // Process Kelas
                        $kelas_ids = explode(',', $row['mengajar_kelas'] ?? '');
                        $kelas_names = [];
                        foreach($kelas_ids as $kid) {
                            foreach($kelas_opts as $ko) {
                                if($ko['id_kelas'] == $kid) $kelas_names[] = $ko['nama_kelas'];
                            }
                        }
                        
                        // Process Mapel
                        $mapel_ids = explode(',', $row['mengajar_mapel'] ?? '');
                        $mapel_names = [];
                        foreach($mapel_ids as $mid) {
                            foreach($mapel_opts as $mo) {
                                if($mo['id_mapel'] == $mid) $mapel_names[] = $mo['nama_mapel'];
                            }
                        }
                    ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <img src="<?php echo !empty($row['foto']) && file_exists('../../assets/img/guru/'.$row['foto']) ? '../../assets/img/guru/'.$row['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($row['nama_lengkap']).'&size=40&background=random'; ?>" 
                                     class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                            </td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['nama_lengkap']; ?></td>
                            <td><?php echo $row['jk'] ? $row['jk'] : '-'; ?></td>
                            <td><?php echo $row['password_plain']; ?></td>
                            <td>
                                <?php 
                                if(!empty($kelas_names)) {
                                    foreach($kelas_names as $kn) {
                                        echo '<span class="badge bg-info me-1">'.$kn.'</span>';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if(!empty($mapel_names)) {
                                    foreach($mapel_names as $mn) {
                                        echo '<span class="badge bg-secondary me-1">'.$mn.'</span>';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                    data-id="<?php echo $row['id_user']; ?>" 
                                    data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                    data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>"
                                    data-jk="<?php echo $row['jk']; ?>"
                                    data-password="<?php echo htmlspecialchars($row['password_plain']); ?>"
                                    data-kelas="<?php echo htmlspecialchars($row['mengajar_kelas'] ?? ''); ?>"
                                    data-mapel="<?php echo htmlspecialchars($row['mengajar_mapel'] ?? ''); ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('guru.php?delete=<?php echo $row['id_user']; ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">Tambah Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">NUPTK</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="jk" required>
                            <option value="">-- Pilih --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mengajar Kelas</label>
                        <select class="form-select select2-add" name="mengajar_kelas[]" multiple="multiple" style="width: 100%">
                            <?php foreach($kelas_opts as $k): ?>
                                <option value="<?php echo $k['id_kelas']; ?>"><?php echo $k['nama_kelas']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mengajar Mapel</label>
                        <select class="form-select select2-add" name="mengajar_mapel[]" multiple="multiple" style="width: 100%">
                            <?php foreach($mapel_opts as $m): ?>
                                <option value="<?php echo $m['id_mapel']; ?>"><?php echo $m['nama_mapel']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" name="password" required>
                        <div class="form-text text-muted">Password akan tersimpan dan terlihat.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id_user" id="edit_id_user">
                    <div class="mb-3">
                        <label class="form-label">NUPTK</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" id="edit_nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="jk" id="edit_jk" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mengajar Kelas</label>
                        <select class="form-select select2-edit" name="mengajar_kelas[]" id="edit_mengajar_kelas" multiple="multiple" style="width: 100%">
                            <?php foreach($kelas_opts as $k): ?>
                                <option value="<?php echo $k['id_kelas']; ?>"><?php echo $k['nama_kelas']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mengajar Mapel</label>
                        <select class="form-select select2-edit" name="mengajar_mapel[]" id="edit_mengajar_mapel" multiple="multiple" style="width: 100%">
                            <?php foreach($mapel_opts as $m): ?>
                                <option value="<?php echo $m['id_mapel']; ?>"><?php echo $m['nama_mapel']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" name="password" id="edit_password" placeholder="Kosongkan jika tidak diubah">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ganti Foto</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                        <div class="form-text">Biarkan kosong jika tidak ingin mengganti foto.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Data Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="importForm">
                <div class="modal-body">
                    <div class="card bg-success bg-opacity-10 border border-success text-success p-3 rounded mb-3 text-center">
                        <i class="fas fa-info-circle me-1"></i> Gunakan format file Excel (.xlsx) dengan urutan kolom:
                        <strong>NUPTK, Nama Lengkap, Password</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File Excel</label>
                        <input type="file" class="form-control" name="file_import" accept=".xlsx" required>
                    </div>
                    <div class="mb-3">
                        <a href="../../assets/template_guru.xlsx" class="btn btn-outline-success btn-sm" download>
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>
                    <!-- Progress Bar (Hidden by default) -->
                    <div class="progress d-none" id="importProgress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">Sedang memproses...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import" class="btn btn-success" onclick="document.getElementById('importProgress').classList.remove('d-none');">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    function confirmSyncSimadGuru() {
        Swal.fire({
            title: 'Sinkron Data Guru SIMAD?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Sinkron',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                var inputSync = document.createElement('input');
                inputSync.type = 'hidden';
                inputSync.name = 'sync_simad_guru';
                inputSync.value = '1';
                form.appendChild(inputSync);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    $(document).ready(function() {
        // Init Select2 for Add Modal
        $('.select2-add').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addModal')
        });

        // Init Select2 for Edit Modal
        $('.select2-edit').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editModal')
        });

        // Handle Edit Button Click
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).attr('data-id');
            var username = $(this).attr('data-username');
            var nama = $(this).attr('data-nama');
            var jk = $(this).attr('data-jk');
            var password = $(this).attr('data-password');
            var kelas = $(this).attr('data-kelas');
            var mapel = $(this).attr('data-mapel');
            
            $('#edit_id_user').val(id);
            $('#edit_username').val(username);
            $('#edit_nama_lengkap').val(nama);
            $('#edit_jk').val(jk);
            $('#edit_password').val(password);
            
            // Set Select2 values
            if(kelas) {
                var kelasArr = kelas.split(',');
                $('#edit_mengajar_kelas').val(kelasArr).trigger('change');
            } else {
                $('#edit_mengajar_kelas').val(null).trigger('change');
            }

            if(mapel) {
                var mapelArr = mapel.split(',');
                $('#edit_mengajar_mapel').val(mapelArr).trigger('change');
            } else {
                $('#edit_mengajar_mapel').val(null).trigger('change');
            }
        });
        
        // Reset form when modal is closed
        $('#addModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $('.select2-add').val(null).trigger('change');
        });
    });
</script>
