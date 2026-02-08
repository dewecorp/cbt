<?php
include __DIR__ . '/../../config/database.php';
$res = mysqli_query($koneksi, "DESCRIBE soal");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
