<?php
include __DIR__ . '/../../config/database.php';
echo "--- SISWA ---\n";
$res = mysqli_query($koneksi, "DESCRIBE siswa");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>