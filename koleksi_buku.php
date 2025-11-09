<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Parameter pencarian
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';

// Query dasar dengan join
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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Buku - Perpustakaan Digital</title>
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
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="flex">
        <div class="w-64 bg-gray-800 text-gray-100 min-h-screen p-4">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold">Koleksi Buku</h1>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" placeholder="Cari..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-700 bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <div class="relative">
                        <i class="fas fa-bell text-gray-400 text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">3</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=Admin+Perpus&background=0D8ABC&color=fff" alt="Admin" class="w-10 h-10 rounded-full">
                        <span class="font-medium"><?= $_SESSION['admin_nama'] ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
            
            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
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
            
            <!-- Pesan Sukses/Error -->
            <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-900 border border-green-700 text-green-300 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-900 border border-red-700 text-red-300 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
            <?php endif; ?>
            
            <!-- Filter dan Tambah Buku -->
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-6 border border-gray-700">
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
                            <a href="koleksi_buku.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center space-x-2">
                                <i class="fas fa-refresh"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </div>
                    <a href="tambah_buku.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2 w-full lg:w-auto justify-center">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Buku</span>
                    </a>
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Stok</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach($buku as $b): 
                                // PERBAIKAN: Hitung jumlah buku yang sedang dipinjam
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
                                    <span class="font-medium"><?= $tersedia ?></span>
                                    <span class="text-gray-400 text-sm">/ <?= $b['stok'] ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="<?= $status_class ?> text-xs px-2 py-1 rounded-full"><?= $status ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <a href="edit_buku.php?id=<?= $b['id_buku'] ?>" class="text-blue-400 hover:text-blue-300 p-2 rounded-lg hover:bg-gray-600" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="hapus_buku.php?id=<?= $b['id_buku'] ?>" 
                                        class="text-red-400 hover:text-red-300 p-2 rounded-lg hover:bg-gray-600"
                                        onclick="return hapusBuku(this);" 
                                        title="Hapus">
                                        <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="detail_buku.php?id=<?= $b['id_buku'] ?>" class="text-green-400 hover:text-green-300 p-2 rounded-lg hover:bg-gray-600" title="Detail">
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
                        <p class="text-sm mt-2">Coba ubah filter pencarian atau tambahkan buku baru</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

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
        });
        
        // SweetAlert untuk konfirmasi hapus buku
        function hapusBuku(el) {
            event.preventDefault();
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Data buku akan hilang permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#1f2937',
                color: '#f9fafb'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = el.href;
                }
            });
            return false;
        }
    </script>
</body>
</html>