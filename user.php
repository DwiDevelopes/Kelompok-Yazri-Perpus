<!DOCTYPE html>
<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';

$query = "
    SELECT b.*, k.nama_kategori 
    FROM buku b 
    JOIN kategori k ON b.id_kategori = k.id_kategori 
    WHERE 1=1
";

$params = [];

// Filter pencarian
if (!empty($search)) {
    $query .= " AND (b.judul LIKE ? OR b.pengarang LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filter kategori
if (!empty($kategori_filter)) {
    $query .= " AND b.id_kategori = ?";
    $params[] = $kategori_filter;
}

$query .= " ORDER BY b.judul";

// Eksekusi query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$buku = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil kategori untuk form filter
$kategori = $pdo->query("SELECT * FROM kategori")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart
$chart_kategori = $pdo->query("
    SELECT k.nama_kategori, COUNT(b.id_buku) as jumlah 
    FROM kategori k 
    LEFT JOIN buku b ON k.id_kategori = b.id_kategori 
    GROUP BY k.id_kategori
")->fetchAll(PDO::FETCH_ASSOC);

// PERBAIKAN: Query untuk menghitung buku tersedia dan habis dengan benar
$status_buku_data = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN b.stok > COALESCE(p.dipinjam, 0) THEN 1 ELSE 0 END) as tersedia,
        SUM(CASE WHEN b.stok <= COALESCE(p.dipinjam, 0) OR b.stok = 0 THEN 1 ELSE 0 END) as habis
    FROM buku b
    LEFT JOIN (
        SELECT id_buku, COUNT(*) as dipinjam 
        FROM peminjaman 
        WHERE status IN ('Dipinjam', 'Terlambat') 
        GROUP BY id_buku
    ) p ON b.id_buku = p.id_buku
")->fetch(PDO::FETCH_ASSOC);
?>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Digital - Koleksi Buku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="icon.png">
    <style>
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #1e293b;
        }
        ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Active nav link */
        .nav-link.active {
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
        }
        
        /* Hero section */
        .hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <!-- Navbar -->
    <nav class="bg-gray-800 sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-book text-blue-400 text-2xl"></i>
                    <span class="text-xl font-bold">Perpustakaan Digital</span>
                </div>
                
                <div class="hidden md:flex space-x-8">
                    <a href="#beranda" class="nav-link active text-lg font-medium hover:text-blue-400 transition-colors">Beranda</a>
                    <a href="#koleksi" class="nav-link text-lg font-medium hover:text-blue-400 transition-colors">Koleksi Buku</a>
                    <a href="#kategori" class="nav-link text-lg font-medium hover:text-blue-400 transition-colors">Kategori</a>
                    <a href="#statistik" class="nav-link text-lg font-medium hover:text-blue-400 transition-colors">Statistik</a>
                    <a href="#tentang" class="nav-link text-lg font-medium hover:text-blue-400 transition-colors">Tentang</a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" placeholder="Cari..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-700 bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=Admin+Perpus&background=0D8ABC&color=fff" alt="Admin" class="w-10 h-10 rounded-full">
                        <span class="font-medium hidden md:inline"><?= $_SESSION['admin_nama'] ?? 'Admin' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Button -->
    <div class="md:hidden fixed bottom-4 right-4 z-50">
        <button id="mobileMenuButton" class="bg-blue-600 text-white p-4 rounded-full shadow-lg">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="fixed inset-0 bg-gray-900 bg-opacity-95 z-40 hidden">
        <div class="flex flex-col items-center justify-center h-full space-y-8">
            <a href="#beranda" class="nav-link text-2xl font-medium text-white hover:text-blue-400 transition-colors">Beranda</a>
            <a href="#koleksi" class="nav-link text-2xl font-medium text-white hover:text-blue-400 transition-colors">Koleksi Buku</a>
            <a href="#kategori" class="nav-link text-2xl font-medium text-white hover:text-blue-400 transition-colors">Kategori</a>
            <a href="#statistik" class="nav-link text-2xl font-medium text-white hover:text-blue-400 transition-colors">Statistik</a>
            <a href="#tentang" class="nav-link text-2xl font-medium text-white hover:text-blue-400 transition-colors">Tentang</a>
            <button id="closeMobileMenu" class="absolute top-8 right-8 text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        <!-- Beranda Section -->
        <section id="beranda" class="hero min-h-screen flex items-center">
            <div class="container mx-auto px-4 py-16">
                <div class="max-w-4xl mx-auto text-center">
                    <h1 class="text-5xl md:text-7xl font-bold mb-6">Selamat Datang di <span class="text-blue-400">Perpustakaan Digital</span></h1>
                    <p class="text-xl md:text-2xl text-gray-300 mb-10">Jelajahi koleksi buku kami yang lengkap dan temukan pengetahuan baru</p>
                    <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-6">
                        <a href="#koleksi" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg text-lg font-medium transition-colors flex items-center justify-center space-x-2">
                            <i class="fas fa-book-open"></i>
                            <span>Jelajahi Koleksi</span>
                        </a>
                        <a href="#kategori" class="bg-gray-700 hover:bg-gray-600 text-white px-8 py-4 rounded-lg text-lg font-medium transition-colors flex items-center justify-center space-x-2">
                            <i class="fas fa-tags"></i>
                            <span>Lihat Kategori</span>
                        </a>
                    </div>
                </div>
                
                <!-- Fitur Unggulan -->
                <div class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-gray-800 bg-opacity-50 rounded-xl p-6 border border-gray-700 text-center">
                        <div class="bg-blue-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-book text-blue-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Koleksi Lengkap</h3>
                        <p class="text-gray-400">Ribuan buku dari berbagai kategori dan genre tersedia untuk Anda baca</p>
                    </div>
                    
                    <div class="bg-gray-800 bg-opacity-50 rounded-xl p-6 border border-gray-700 text-center">
                        <div class="bg-green-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-search text-green-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Pencarian Cepat</h3>
                        <p class="text-gray-400">Temukan buku yang Anda cari dengan mudah menggunakan sistem pencarian kami</p>
                    </div>
                    
                    <div class="bg-gray-800 bg-opacity-50 rounded-xl p-6 border border-gray-700 text-center">
                        <div class="bg-purple-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-bar text-purple-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Statistik Detail</h3>
                        <p class="text-gray-400">Pantau statistik koleksi buku dengan grafik dan diagram yang informatif</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Koleksi Buku Section -->
        <section id="koleksi" class="py-16 bg-gray-800">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold mb-4">Koleksi Buku</h2>
                    <p class="text-xl text-gray-400 max-w-2xl mx-auto">Jelajahi koleksi buku digital kami yang lengkap dari berbagai kategori dan genre</p>
                </div>
                
                <!-- Filter dan Pencarian -->
                <div class="bg-gray-800 rounded-xl shadow p-6 mb-8 border border-gray-700">
                    <form method="GET" class="flex flex-col lg:flex-row justify-between items-start lg:items-center space-y-4 lg:space-y-0">
                        <div class="flex flex-col lg:flex-row space-y-4 lg:space-y-0 lg:space-x-4 w-full lg:w-auto">
                            <select name="kategori" class="border border-gray-700 bg-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Kategori</option>
                                <?php foreach($kategori as $kat): ?>
                                <option value="<?= $kat['id_kategori'] ?>" <?= $kategori_filter == $kat['id_kategori'] ? 'selected' : '' ?>>
                                    <?= $kat['nama_kategori'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="search" placeholder="Cari judul atau pengarang..." 
                                value="<?= htmlspecialchars($search) ?>"
                                class="border border-gray-700 bg-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full lg:w-64">
                            <div class="flex space-x-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2">
                                    <i class="fas fa-search"></i>
                                    <span>Cari</span>
                                </button>
                                <a href="user.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center space-x-2">
                                    <i class="fas fa-refresh"></i>
                                    <span>Reset</span>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tabel Buku -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Cover</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Judul Buku</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Pengarang</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Kategori</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tahun</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach($buku as $b): 
                                    // Hitung status buku
                                    $status_buku = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_buku = ? AND status IN ('Dipinjam', 'Terlambat')");
                                    $status_buku->execute([$b['id_buku']]);
                                    $dipinjam = $status_buku->fetchColumn();
                                    $tersedia = $b['stok'] - $dipinjam;
                                    $status = $tersedia > 0 ? 'Tersedia' : 'Habis';
                                    $status_class = $status == 'Tersedia' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300';
                                ?>
                                <tr class="hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <?php if ($b['gambar']): ?>
                                        <img src="<?= $b['gambar'] ?>" alt="Cover" class="w-12 h-16 object-cover rounded">
                                        <?php else: ?>
                                        <div class="w-12 h-16 bg-gray-700 rounded flex items-center justify-center">
                                            <i class="fas fa-book text-gray-400"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($b['judul']) ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($b['pengarang']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="bg-gray-700 text-gray-300 text-xs px-2 py-1 rounded-full">
                                            <?= $b['nama_kategori'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><?= $b['tahun_terbit'] ?></td>
                                    <td class="px-4 py-3">
                                        <span class="<?= $status_class ?> text-xs px-2 py-1 rounded-full"><?= $status ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <a href="buku.php?id=<?= $b['id_buku'] ?>" class="text-green-400 hover:text-green-300 p-2 rounded-lg hover:bg-gray-600" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (empty($buku)): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-book-open text-4xl mb-4"></i>
                            <p class="text-lg">Tidak ada buku yang ditemukan</p>
                            <p class="text-sm mt-2">Coba ubah filter pencarian</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Kategori Section -->
        <section id="kategori" class="py-16 bg-gray-900">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold mb-4">Kategori Buku</h2>
                    <p class="text-xl text-gray-400 max-w-2xl mx-auto">Temukan buku berdasarkan kategori yang Anda minati</p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    <?php foreach($kategori as $kat): 
                        // Hitung jumlah buku per kategori
                        $count_buku = $pdo->prepare("SELECT COUNT(*) FROM buku WHERE id_kategori = ?");
                        $count_buku->execute([$kat['id_kategori']]);
                        $jumlah = $count_buku->fetchColumn();
                    ?>
                    <div class="bg-gray-800 rounded-xl p-6 text-center border border-gray-700 hover:border-blue-500 transition-colors cursor-pointer">
                        <div class="bg-blue-900 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-tag text-blue-400"></i>
                        </div>
                        <h3 class="font-bold mb-1"><?= $kat['nama_kategori'] ?></h3>
                        <p class="text-gray-400 text-sm"><?= $jumlah ?> buku</p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Statistik Section -->
        <section id="statistik" class="py-16 bg-gray-800">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold mb-4">Statistik Koleksi</h2>
                    <p class="text-xl text-gray-400 max-w-2xl mx-auto">Analisis visual dari koleksi buku perpustakaan kami</p>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400">Total Buku</p>
                                <h3 class="text-3xl font-bold mt-2"><?= count($buku) ?></h3>
                            </div>
                            <div class="bg-blue-900 p-3 rounded-lg">
                                <i class="fas fa-book text-blue-400 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400">Buku Tersedia</p>
                                <h3 class="text-3xl font-bold mt-2"><?= $status_buku_data['tersedia'] ?></h3>
                            </div>
                            <div class="bg-green-900 p-3 rounded-lg">
                                <i class="fas fa-check-circle text-green-400 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400">Buku Habis</p>
                                <h3 class="text-3xl font-bold mt-2"><?= $status_buku_data['habis'] ?></h3>
                            </div>
                            <div class="bg-red-900 p-3 rounded-lg">
                                <i class="fas fa-times-circle text-red-400 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Chart Distribusi Kategori -->
                    <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                        <h2 class="text-xl font-bold mb-4">Distribusi Buku per Kategori</h2>
                        <div class="h-80">
                            <canvas id="kategoriChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Chart Status Buku -->
                    <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                        <h2 class="text-xl font-bold mb-4">Status Ketersediaan Buku</h2>
                        <div class="h-80">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tentang Section -->
        <section id="tentang" class="py-16 bg-gray-900">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-12">
                        <h2 class="text-4xl font-bold mb-4">Tentang Perpustakaan Digital</h2>
                        <p class="text-xl text-gray-400">Misi dan visi kami dalam menyediakan akses pengetahuan</p>
                    </div>
                    
                    <div class="bg-gray-800 rounded-xl p-8 border border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-2xl font-bold mb-4 text-blue-400">Misi Kami</h3>
                                <p class="text-gray-300 mb-6">Menyediakan akses mudah dan luas terhadap berbagai sumber pengetahuan melalui platform digital yang inovatif dan user-friendly.</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-400 mt-1 mr-3"></i>
                                        <span>Menyediakan koleksi buku digital yang lengkap dan berkualitas</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-400 mt-1 mr-3"></i>
                                        <span>Memudahkan pencarian dan penemuan buku yang relevan</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-400 mt-1 mr-3"></i>
                                        <span>Mendorong minat baca melalui teknologi yang modern</span>
                                    </li>
                                </ul>
                            </div>
                            
                            <div>
                                <h3 class="text-2xl font-bold mb-4 text-blue-400">Visi Kami</h3>
                                <p class="text-gray-300 mb-6">Menjadi platform perpustakaan digital terdepan yang menginspirasi masyarakat untuk terus belajar dan berkembang melalui akses pengetahuan yang mudah.</p>
                                <div class="bg-gray-700 rounded-lg p-4">
                                    <h4 class="font-bold mb-2">Kontak Kami</h4>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <i class="fas fa-envelope text-blue-400 mr-3"></i>
                                            <span>info@perpustakaandigital.com</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-phone text-blue-400 mr-3"></i>
                                            <span>(021) 1234-5678</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-map-marker-alt text-blue-400 mr-3"></i>
                                            <span>Jl. Perpustakaan No. 123, Jakarta</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 py-8 border-t border-gray-700">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <i class="fas fa-book text-blue-400 text-2xl"></i>
                    <span class="text-xl font-bold">Perpustakaan Digital</span>
                </div>
                <div class="text-gray-400 text-center md:text-right">
                    <p>&copy; 2023 Perpustakaan Digital. Semua hak dilindungi.</p>
                    <p class="text-sm mt-1">Dibuat dengan <i class="fas fa-heart text-red-400"></i> untuk para pencinta buku</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Chart.js Implementation
        document.addEventListener('DOMContentLoaded', function() {
            // Chart Distribusi Kategori
            const kategoriCtx = document.getElementById('kategoriChart').getContext('2d');
            const kategoriChart = new Chart(kategoriCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($chart_kategori, 'nama_kategori')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($chart_kategori, 'jumlah')) ?>,
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(236, 72, 153, 0.7)',
                            'rgba(14, 165, 233, 0.7)',
                            'rgba(99, 102, 241, 0.7)',
                            'rgba(239, 68, 68, 0.7)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(236, 72, 153, 1)',
                            'rgba(14, 165, 233, 1)',
                            'rgba(99, 102, 241, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                padding: 20
                            }
                        }
                    }
                }
            });
            
            // Chart Status Buku
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Tersedia', 'Habis'],
                    datasets: [{
                        data: [
                            <?= $status_buku_data['tersedia'] ?>,
                            <?= $status_buku_data['habis'] ?>
                        ],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(239, 68, 68, 0.7)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                padding: 20
                            }
                        }
                    }
                }
            });
            
            // Navbar active state
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.nav-link');
            
            function setActiveNavLink() {
                let current = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    
                    if (scrollY >= (sectionTop - 200)) {
                        current = section.getAttribute('id');
                    }
                });
                
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${current}`) {
                        link.classList.add('active');
                    }
                });
            }
            
            window.addEventListener('scroll', setActiveNavLink);
            
            // Mobile menu functionality
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');
            const closeMobileMenu = document.getElementById('closeMobileMenu');
            
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.remove('hidden');
            });
            
            closeMobileMenu.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
            
            // Close mobile menu when clicking on a link
            const mobileMenuLinks = document.querySelectorAll('#mobileMenu a');
            mobileMenuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                });
            });
        });
    </script>
</body>
</html>