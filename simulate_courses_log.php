<?php
$uid = 19; 
include 'config/database.php';

// Logic copy-pasted from courses.php (modified version)
    $qg = mysqli_query($koneksi, "SELECT mengajar_kelas, mengajar_mapel FROM users WHERE id_user=".$uid);
    $list_kelas = [];
    $list_mapel = [];
    
    if ($qg && mysqli_num_rows($qg)>0) {
        $dg = mysqli_fetch_assoc($qg);
        if (!empty($dg['mengajar_kelas'])) {
            foreach(explode(',', $dg['mengajar_kelas']) as $kid){ $kid=trim($kid); if($kid!=='') $list_kelas[] = (int)$kid; }
        }
        if (!empty($dg['mengajar_mapel'])) {
            // Gunakan regex untuk mengambil semua angka (ID mapel) dari string CSV
            preg_match_all('/\d+/', $dg['mengajar_mapel'], $matches);
            if (isset($matches[0]) && !empty($matches[0])) {
                foreach($matches[0] as $mid) { $list_mapel[] = (int)$mid; }
            }
        }
    }
    
    $list_mapel = array_unique($list_mapel);
    if (!empty($list_mapel)) {
        $in_m = implode(',', $list_mapel);
        // DEBUG: Log the query
        $log_debug = "User: $uid | Raw: " . ($dg['mengajar_mapel'] ?? 'NULL') . " | IDs: $in_m \n";
        echo "Log: $log_debug";
        
        $mapel = mysqli_query($koneksi, "SELECT id_mapel,nama_mapel FROM mapel WHERE id_mapel IN ($in_m) ORDER BY nama_mapel ASC");
        while($m = mysqli_fetch_assoc($mapel)) {
            echo "Found: " . $m['nama_mapel'] . "\n";
        }
    } else {
        echo "No mapels found logic path.\n";
    }
