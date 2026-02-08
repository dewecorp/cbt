<?php
require 'config/database.php';
$res = mysqli_query($koneksi, 'SHOW TABLES');
while($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
}
?>