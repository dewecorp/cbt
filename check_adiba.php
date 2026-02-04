<?php
include 'config/database.php';
$nama = 'ADIBA NUHA AZZAHRA';
echo "Checking User/Siswa: $nama\n";

echo "\n--- Tabel USERS ---\n";
$q_u = mysqli_query($koneksi, "SELECT * FROM users WHERE nama_lengkap LIKE '%$nama%'");
if(mysqli_num_rows($q_u) > 0) {
    while($r = mysqli_fetch_assoc($q_u)) {
        print_r($r);
    }
} else {
    echo "Tidak ditemukan di tabel users.\n";
}

echo "\n--- Tabel SISWA ---\n";
$q_s = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nama_siswa LIKE '%$nama%'");
if(mysqli_num_rows($q_s) > 0) {
    while($r = mysqli_fetch_assoc($q_s)) {
        print_r($r);
    }
} else {
    echo "Tidak ditemukan di tabel siswa.\n";
}
?>