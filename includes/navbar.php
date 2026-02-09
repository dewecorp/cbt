<?php
// Fetch Logo and Name for Mobile Header
$m_school_name = 'E-Learning';
$m_school_logo = '';
if (isset($koneksi)) {
    $m_rs = mysqli_query($koneksi, "SELECT nama_sekolah, logo FROM setting LIMIT 1");
    if ($m_rs && mysqli_num_rows($m_rs) > 0) {
        $m_st = mysqli_fetch_assoc($m_rs);
        if (!empty($m_st['nama_sekolah'])) $m_school_name = $m_st['nama_sekolah'];
        if (!empty($m_st['logo'])) $m_school_logo = $m_st['logo'];
    }
}
?>

<!-- Mobile Header (Logo + App Name + Madrasah Name) -->
<div class="mobile-header d-flex align-items-center justify-content-center py-2 bg-white border-bottom shadow-sm d-md-none sticky-top" style="z-index: 1020;">
    <div class="d-flex align-items-center">
        <?php if($m_school_logo): ?>
            <img src="<?php echo $base_url; ?>assets/img/<?php echo $m_school_logo; ?>" alt="Logo" height="45" class="me-3">
        <?php endif; ?>
        <div class="text-start">
            <div class="fw-bold text-success lh-1 mb-1" style="font-size: 1.25rem; font-family: 'Poppins', sans-serif;">E-Learning</div>
            <div class="fw-bold text-dark lh-1" style="font-size: 0.9rem; font-family: 'Poppins', sans-serif;"><?php echo $m_school_name; ?></div>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top w-100 d-none d-md-flex">
    <div class="container-fluid">
        <!-- Sidebar Toggle removed for mobile bottom nav -->
        <?php
        $school_name_nav = 'CBT MI';
        if (isset($koneksi)) {
            $rsn = mysqli_query($koneksi, "SELECT nama_sekolah FROM setting LIMIT 1");
            if ($rsn && mysqli_num_rows($rsn) > 0) {
                $sn = mysqli_fetch_assoc($rsn);
                if (!empty($sn['nama_sekolah'])) $school_name_nav = $sn['nama_sekolah'];
            }
        }
        ?>
        <a class="navbar-brand" href="#"><?php echo $school_name_nav; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Realtime Clock -->
            <div class="ms-auto me-3 text-white d-none d-lg-block">
                <i class="fas fa-clock me-1"></i>
                <span id="realtime-clock">
                    <?php 
                    // Fallback PHP
                    $hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                    $bulan_indo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    echo $hari_indo[date('l')] . ', ' . date('d') . ' ' . $bulan_indo[(int)date('m')] . ' ' . date('Y') . ' ' . date('H:i:s') . ' WIB'; 
                    ?>
                </span>
            </div>

            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 'guru'): 
                    $my_id = $_SESSION['user_id'];
                    $role_param = isset($_SESSION['level']) ? '?role=' . $_SESSION['level'] : '';
                    
                    $q_unread = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM notifications WHERE user_id='$my_id' AND is_read=0");
                    $r_unread = mysqli_fetch_assoc($q_unread);
                    $unread_count = $r_unread['count'];
                    
                    $q_notifs = mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id='$my_id' ORDER BY created_at DESC LIMIT 10");
                ?>
                <li class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                <?php echo $unread_count; ?>
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="notifDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li><h6 class="dropdown-header bg-success text-white">Notifikasi</h6></li>
                        <?php if(mysqli_num_rows($q_notifs) > 0): ?>
                            <?php 
                            $bulan_indo_notif = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                            while($n = mysqli_fetch_assoc($q_notifs)): 
                                $ts = strtotime($n['created_at']);
                                $tgl_notif = date('d', $ts) . ' ' . $bulan_indo_notif[(int)date('m', $ts)] . ' ' . date('Y, H:i', $ts);
                                $role_param = isset($_SESSION['level']) ? '&role=' . $_SESSION['level'] : '';
                            ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-start py-2" href="<?php echo $base_url; ?>modules/notifikasi/read_notif.php?id=<?php echo $n['id'] . $role_param; ?>">
                                        <div class="me-3 mt-1">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                        </div>
                                        <div class="w-100">
                                            <div class="small text-muted mb-1"><?php echo $tgl_notif; ?></div>
                                            <div class="<?php echo ($n['is_read'] == 0) ? 'fw-bold' : ''; ?>" style="white-space: normal; font-size: 0.9rem;">
                                                <?php echo $n['message']; ?> <!-- Output raw HTML for bold names -->
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-0"></li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><div class="dropdown-item text-center small text-muted py-3">Tidak ada notifikasi baru</div></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php 
                         $u_foto = isset($_SESSION['foto']) ? $_SESSION['foto'] : '';
                         $u_nama = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User';
                         $u_level = isset($_SESSION['level']) ? $_SESSION['level'] : '';
                         
                         $root_path = dirname(__DIR__);
                         $foto_path_web = '';
                         
                         if ($u_level == 'guru') {
                             $foto_file = $root_path . '/assets/img/guru/' . $u_foto;
                             $foto_path_web = 'assets/img/guru/' . $u_foto;
                         } else {
                             // Admin or others (default to users upload)
                             $foto_file = $root_path . '/assets/uploads/users/' . $u_foto;
                             $foto_path_web = 'assets/uploads/users/' . $u_foto;
                         }
                         
                         if (!empty($u_foto) && file_exists($foto_file)) {
                             echo '<img src="'.$base_url.$foto_path_web.'" alt="User" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">';
                         } else {
                             $initial = strtoupper(substr($u_nama, 0, 1));
                            echo '<div class="rounded-circle bg-light text-success d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 32px; height: 32px;">'.$initial.'</div>';
                        }
                        ?>
                        <span class="d-none d-lg-inline"><?php echo $u_nama; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>logout.php?role=<?php echo (isset($_SESSION['level']) ? $_SESSION['level'] : ''); ?>"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
function updateClock() {
    const clockElement = document.getElementById('realtime-clock');
    if (!clockElement) return;

    const now = new Date();
    
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    const dayName = days[now.getDay()];
    const dayDate = now.getDate().toString().padStart(2, '0');
    const monthName = months[now.getMonth()];
    const year = now.getFullYear();
    
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    
    const timeString = `${dayName}, ${dayDate} ${monthName} ${year} ${hours}:${minutes}:${seconds} WIB`;
    
    clockElement.textContent = timeString;
}

// Update every second
setInterval(updateClock, 1000);
// Initial call
updateClock();

document.addEventListener("DOMContentLoaded", function(event) {
   const toggle = document.getElementById('sidebarToggle');
   const sidebar = document.getElementById('sidebar');
   const closeBtn = document.getElementById('sidebarClose');
   const overlay = document.getElementById('overlay');
   
   if(toggle && sidebar && overlay){
       // Toggle Sidebar
       toggle.addEventListener('click', ()=>{
           sidebar.classList.add('show-sidebar');
           overlay.classList.add('show');
       });

       // Close Sidebar via Button
       if(closeBtn) {
           closeBtn.addEventListener('click', ()=>{
               sidebar.classList.remove('show-sidebar');
               overlay.classList.remove('show');
           });
       }

       // Close Sidebar via Overlay
       overlay.addEventListener('click', ()=>{
           sidebar.classList.remove('show-sidebar');
           overlay.classList.remove('show');
       });
   }

   document.querySelectorAll('a[href$="logout.php"]').forEach(function(el){
       el.addEventListener('click', function(e){
           e.preventDefault();
           const href = el.getAttribute('href');
           Swal.fire({
               title: 'Konfirmasi Logout',
               text: 'Anda yakin ingin keluar?',
               icon: 'warning',
               showCancelButton: true,
               confirmButtonColor: '#d33',
               cancelButtonColor: '#198754',
               confirmButtonText: 'Ya, Logout',
               cancelButtonText: 'Batal'
           }).then((result) => {
               if (result.isConfirmed) {
                   window.location.href = href;
               }
           });
       });
   });

   document.addEventListener('click', function(e){
       const anchor = e.target.closest('a[href$="logout.php"]');
       if (!anchor) return;
       e.preventDefault();
       const href = anchor.getAttribute('href');
       Swal.fire({
           title: 'Konfirmasi Logout',
           text: 'Anda yakin ingin keluar?',
           icon: 'warning',
           showCancelButton: true,
           confirmButtonColor: '#d33',
           cancelButtonColor: '#198754',
           confirmButtonText: 'Ya, Logout',
           cancelButtonText: 'Batal'
       }).then((result) => {
           if (result.isConfirmed) {
               window.location.href = href;
           }
       });
   });
});
</script>
