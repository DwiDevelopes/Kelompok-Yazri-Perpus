<div class="flex items-center space-x-2 mb-8 text-white">
    <i class="fa-solid fa-book-open text-blue-500 animate-pulse"></i>
    <h1 class="text-xl font-bold">Perpus Digital</h1>
</div>
    <link rel="icon" type="image/x-icon" href="icon.png">
<nav>
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>

    <!-- Dashboard -->
    <a href="index.php" 
       class="flex items-center space-x-3 py-3 px-2 
       <?= $current_page == 'index.php' ? 'bg-gray-700 text-white' : 'hover:bg-gray-800 text-gray-300' ?> 
       rounded-lg mb-2 transition-all duration-300 ease-in-out group">
        <i class="fas fa-tachometer-alt text-blue-500 group-hover:scale-110 group-hover:rotate-12 transition-transform duration-300"></i>
        <span>Dashboard</span>
    </a>

    <!-- Koleksi Buku -->
    <a href="koleksi_buku.php" 
       class="flex items-center space-x-3 py-3 px-2 
       <?= in_array($current_page, ['koleksi_buku.php','tambah_buku.php','edit_buku.php','detail_buku.php']) ? 'bg-gray-700 text-white' : 'hover:bg-gray-800 text-gray-300' ?> 
       rounded-lg mb-2 transition-all duration-300 ease-in-out group">
        <i class="fas fa-book text-blue-500 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300"></i>
        <span>Koleksi Buku</span>
    </a>

    <!-- Anggota -->
    <a href="anggota.php" 
       class="flex items-center space-x-3 py-3 px-2 
       <?= in_array($current_page, ['anggota.php','edit_anggota.php','detail_anggota.php']) ? 'bg-gray-700 text-white' : 'hover:bg-gray-800 text-gray-300' ?> 
       rounded-lg mb-2 transition-all duration-300 ease-in-out group">
        <i class="fas fa-users text-blue-500 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-300"></i>
        <span>Anggota</span>
    </a>

    <!-- Peminjaman -->
    <a href="peminjaman.php" 
       class="flex items-center space-x-3 py-3 px-2 
       <?= $current_page == 'peminjaman.php' ? 'bg-gray-700 text-white' : 'hover:bg-gray-800 text-gray-300' ?> 
       rounded-lg mb-2 transition-all duration-300 ease-in-out group">
        <i class="fas fa-exchange-alt text-blue-500 group-hover:scale-125 transition-transform duration-300"></i>
        <span>Peminjaman</span>
    </a>

    <!-- Riwayat -->
    <a href="riwayat.php" 
       class="flex items-center space-x-3 py-3 px-2 
       <?= $current_page == 'riwayat.php' ? 'bg-gray-700 text-white' : 'hover:bg-gray-800 text-gray-300' ?> 
       rounded-lg mb-2 transition-all duration-300 ease-in-out group">
        <i class="fas fa-history text-blue-500 group-hover:rotate-180 transition-transform duration-500"></i>
        <span>Riwayat</span>
    </a>

    <!-- QR Code -->
    <a href="qrcode.php" 
       class="flex items-center space-x-3 py-3 px-2 
       <?= $current_page == 'qrcode.php' ? 'bg-gray-700 text-white' : 'hover:bg-gray-800 text-gray-300' ?> 
       rounded-lg mb-2 transition-all duration-300 ease-in-out group">
        <i class="fas fa-qrcode text-blue-500 group-hover:scale-110 transition-transform duration-300"></i>
        <span>QR Code</span>
    </a>

    <!-- Pengaturan -->
    <a href="pengaturan.php" 
       class="flex items-center space-x-3 py-3 px-2 
       <?= $current_page == 'pengaturan.php' ? 'bg-gray-700 text-white' : 'hover:bg-gray-800 text-gray-300' ?> 
       rounded-lg mb-2 transition-all duration-300 ease-in-out group">
        <i class="fas fa-cog text-blue-500 group-hover:spin transition-transform duration-500"></i>
        <span>Pengaturan</span>
    </a>

    <!-- Logout -->
    <a href="logout.php" 
       class="flex items-center space-x-3 py-3 px-2 
       hover:bg-red-600 text-gray-300 hover:text-white 
       rounded-lg mb-2 transition-all duration-300 ease-in-out group">
        <i class="fas fa-sign-out-alt text-blue-500 group-hover:scale-110 group-hover:-rotate-12 transition-transform duration-300"></i>
        <span>Logout</span>
    </a>
</nav>
