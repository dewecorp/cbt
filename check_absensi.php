<?php
require 'config/database.php';
$q = mysqli_query($koneksi, "DESCRIBE absensi");
while($row = mysqli_fetch_assoc($q)) {
    print_r($row);
}
?>