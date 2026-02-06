<?php
session_start();
include 'config/database.php';

// Fetch Settings
$st = ['nama_sekolah' => 'MI Sultan Fattah', 'logo' => '', 'tahun_ajaran' => '', 'semester' => ''];
if (isset($koneksi)) {
    $q_st = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
    if ($q_st && mysqli_num_rows($q_st) > 0) {
        $st = mysqli_fetch_assoc($q_st);
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Cek di tabel users (Admin/Guru)
    $query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND status='aktif'");
    if (mysqli_num_rows($query_user) > 0) {
        $data = mysqli_fetch_assoc($query_user);
        if (password_verify($password, $data['password'])) {
            $_SESSION['user_id'] = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['nama'] = $data['nama_lengkap'];
            $_SESSION['level'] = $data['level'];
            $_SESSION['login_success'] = 1;
            log_activity('login', 'auth', 'login admin/guru');
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        // Cek di tabel siswa
        $query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nisn='$username' AND status='aktif'");
        if (mysqli_num_rows($query_siswa) > 0) {
            $data = mysqli_fetch_assoc($query_siswa);
            // Untuk siswa, password bisa plain text atau hashed, kita asumsi hashed juga, atau defaultnya NIS
            // Sederhananya kita pakai password_verify jika sudah dihash, atau compare langsung jika belum (tapi sebaiknya hash)
            // Untuk awal, kita asumsi password = nis jika belum di set, atau cek hash
            
            // Implementasi sederhana: cek password
            if ($password == $data['password'] || password_verify($password, $data['password'])) {
                 $_SESSION['user_id'] = $data['id_siswa'];
                 $_SESSION['username'] = $data['nisn'];
                 $_SESSION['nama'] = $data['nama_siswa'];
                 $_SESSION['level'] = 'siswa';
                 $_SESSION['id_kelas'] = $data['id_kelas'];
                 $_SESSION['login_success'] = 1;
                 log_activity('login', 'auth', 'login siswa');
                 header("Location: dashboard.php");
                 exit;
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username/NISN tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CBT MI Sultan Fattah</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo mb-3">
                <?php if (!empty($st['logo']) && file_exists('assets/img/' . $st['logo'])): ?>
                    <img src="assets/img/<?php echo $st['logo']; ?>" alt="Logo" style="max-height: 100px; width: auto;">
                <?php else: ?>
                    <i class="fas fa-book-reader fa-4x text-kemenag"></i>
                <?php endif; ?>
            </div>
            
            <h4 class="text-center fw-bold text-kemenag mb-1">E-Learning</h4>
            <h5 class="text-center fw-bold text-dark mb-2 text-uppercase"><?php echo $st['nama_sekolah']; ?></h5>
            
            <?php if(!empty($st['tahun_ajaran'])): ?>
            <p class="text-center text-muted mb-4">
                Tahun Ajaran <?php echo $st['tahun_ajaran']; ?> Semester <?php echo $st['semester']; ?>
            </p>
            <?php else: ?>
            <p class="text-center text-muted mb-4">Silahkan login untuk melanjutkan</p>
            <?php endif; ?>
            
            <?php if($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Login Gagal',
                        text: '<?php echo $error; ?>',
                        icon: 'error',
                        confirmButtonText: 'Coba Lagi'
                    });
                });
            </script>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username / NISN</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="Masukkan Username atau NISN">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan Password">
                    </div>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="login" class="btn btn-kemenag btn-lg">LOGIN</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">&copy; 2026 MI Sultan Fattah Sukosono</small>
            </div>
        </div>
    </div>
    <script src="assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
