<?php
include 'config/database.php';

echo "Searching for Najwah...\n";
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nama_siswa LIKE '%Najwah%'");
$najwah = null;
while ($row = mysqli_fetch_assoc($q_siswa)) {
    print_r($row);
    $najwah = $row;
}

if ($najwah) {
    $id_siswa = $najwah['id_siswa'];
    $id_kelas = $najwah['id_kelas'];
    
    echo "\nSearching for IPAS assignments in Class ID: $id_kelas...\n";
    $q_assign = mysqli_query($koneksi, "
        SELECT a.id_assignment, a.judul, m.nama_mapel 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id_course 
        JOIN mapel m ON c.id_mapel = m.id_mapel
        WHERE c.id_kelas = '$id_kelas' AND m.nama_mapel LIKE '%IPAS%'
    ");
    
    $assignments = [];
    while ($row = mysqli_fetch_assoc($q_assign)) {
        print_r($row);
        $assignments[] = $row['id_assignment'];
    }
    
    if (!empty($assignments)) {
        $ids = implode(',', $assignments);
        echo "\nChecking submissions for Najwah (ID: $id_siswa) in these assignments ($ids)...\n";
        
        $q_sub = mysqli_query($koneksi, "SELECT * FROM submissions WHERE siswa_id = '$id_siswa' AND assignment_id IN ($ids)");
        if (mysqli_num_rows($q_sub) > 0) {
            while ($row = mysqli_fetch_assoc($q_sub)) {
                print_r($row);
            }
        } else {
            echo "No submissions found for Najwah in these assignments.\n";
            
            // Check if there are ANY submissions for Najwah at all
            echo "\nChecking ANY submissions for Najwah...\n";
            $q_any = mysqli_query($koneksi, "SELECT * FROM submissions WHERE siswa_id = '$id_siswa'");
            while ($row = mysqli_fetch_assoc($q_any)) {
                print_r($row);
            }
        }
    } else {
        echo "No IPAS assignments found for this class.\n";
    }
}
?>