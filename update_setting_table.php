<?php
include 'config/database.php';

// Check if table setting exists
$check = mysqli_query($koneksi, "SHOW TABLES LIKE 'setting'");
if (mysqli_num_rows($check) == 0) {
    // Create table
    $query = "CREATE TABLE `setting` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `nama_sekolah` varchar(100) NOT NULL,
      `alamat` text,
      `logo` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    )";
    if (mysqli_query($koneksi, $query)) {
        echo "Tabel setting berhasil dibuat.<br>";
        // Insert default
        mysqli_query($koneksi, "INSERT INTO `setting` (`nama_sekolah`, `alamat`) VALUES ('MI Sultan Fattah Sukosono', 'Sukosono, Jepara')");
    } else {
        echo "Gagal membuat tabel setting: " . mysqli_error($koneksi) . "<br>";
    }
} else {
    echo "Tabel setting sudah ada.<br>";
}
?>