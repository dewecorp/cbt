<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top w-100">
    <div class="container-fluid">
        <button class="btn btn-link text-white d-md-none me-3" type="button" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
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
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?php echo isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="<?php echo $base_url; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
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
               cancelButtonColor: '#3085d6',
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
           cancelButtonColor: '#3085d6',
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
