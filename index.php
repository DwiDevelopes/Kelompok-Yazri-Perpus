<?php
session_start();
require_once 'config.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data statistik
$total_buku = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$anggota_aktif = $pdo->query("SELECT COUNT(*) FROM anggota WHERE status = 'Aktif'")->fetchColumn();
$peminjaman_aktif = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'Dipinjam'")->fetchColumn();
$keterlambatan = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'Terlambat'")->fetchColumn();

// Ambil data untuk chart peminjaman bulanan
$chart_data = [];
for ($i = 1; $i <= 12; $i++) {
    $month = date('Y-m', mktime(0, 0, 0, $i, 1, date('Y')));
    $peminjaman = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE DATE_FORMAT(tanggal_pinjam, '%Y-%m') = ?");
    $peminjaman->execute([$month]);
    $pengembalian = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE status = 'Dikembalikan' AND DATE_FORMAT(tanggal_kembali, '%Y-%m') = ?");
    $pengembalian->execute([$month]);
    
    $chart_data['labels'][] = date('M', mktime(0, 0, 0, $i, 1, date('Y')));
    $chart_data['peminjaman'][] = $peminjaman->fetchColumn();
    $chart_data['pengembalian'][] = $pengembalian->fetchColumn();
}

// Data untuk chart kategori buku
$kategori_data = $pdo->query("
    SELECT k.nama_kategori, COUNT(b.id_buku) as jumlah 
    FROM kategori k 
    LEFT JOIN buku b ON k.id_kategori = b.id_kategori 
    GROUP BY k.id_kategori
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart status peminjaman
$status_data = $pdo->query("
    SELECT status, COUNT(*) as jumlah 
    FROM peminjaman 
    WHERE status IN ('Dipinjam', 'Dikembalikan', 'Terlambat')
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil aktivitas terbaru
$aktivitas = $pdo->query("
    SELECT p.id_peminjaman, b.judul, a.nama, p.tanggal_pinjam, p.status, 'peminjaman' as tipe
    FROM peminjaman p
    JOIN buku b ON p.id_buku = b.id_buku
    JOIN anggota a ON p.id_anggota = a.id_anggota
    ORDER BY p.tanggal_pinjam DESC
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil buku terbaru
$buku_terbaru = $pdo->query("
    SELECT b.*, k.nama_kategori 
    FROM buku b 
    JOIN kategori k ON b.id_kategori = k.id_kategori 
    ORDER BY b.id_buku DESC 
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perpustakaan Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="icon.png">
    <style>
        /* Custom scrollbar untuk dark mode */
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
    <!-- Sidebar -->
    <div class="flex">
        <div class="w-64 bg-gray-800 text-gray-200 min-h-screen p-4">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold">Dashboard Perpustakaan</h1>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" placeholder="Cari..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-700 bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <div class="relative">
                        <i class="fas fa-bell text-gray-400 text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs"><?= $keterlambatan ?></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=Admin+Perpus&background=0D8ABC&color=fff" alt="Admin" class="w-10 h-10 rounded-full">
                        <span class="font-medium"><?= $_SESSION['admin_nama'] ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Total Buku</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $total_buku ?></h3>
                        </div>
                        <div class="bg-blue-900 p-3 rounded-lg">
                            <i class="fas fa-book text-blue-400 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-green-400 text-sm mt-4"><i class="fas fa-arrow-up"></i> 12% dari bulan lalu</p>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Anggota Aktif</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $anggota_aktif ?></h3>
                        </div>
                        <div class="bg-green-900 p-3 rounded-lg">
                            <i class="fas fa-users text-green-400 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-green-400 text-sm mt-4"><i class="fas fa-arrow-up"></i> 8% dari bulan lalu</p>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Peminjaman Aktif</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $peminjaman_aktif ?></h3>
                        </div>
                        <div class="bg-yellow-900 p-3 rounded-lg">
                            <i class="fas fa-exchange-alt text-yellow-400 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-red-400 text-sm mt-4"><i class="fas fa-arrow-down"></i> 3% dari bulan lalu</p>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Keterlambatan</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $keterlambatan ?></h3>
                        </div>
                        <div class="bg-red-900 p-3 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-red-400 text-sm mt-4"><i class="fas fa-arrow-up"></i> 5% dari bulan lalu</p>
                </div>
            </div>
            
            <!-- Charts and Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Chart Peminjaman Bulanan -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700 lg:col-span-2">
                    <h2 class="text-xl font-bold mb-4">Statistik Peminjaman Bulanan</h2>
                    <div class="h-80">
                        <canvas id="loanChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart Kategori Buku -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Distribusi Kategori Buku</h2>
                    <div class="h-80">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Second Row of Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Chart Status Peminjaman -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Status Peminjaman</h2>
                    <div class="h-80">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700 lg:col-span-2">
                    <h2 class="text-xl font-bold mb-4">Aktivitas Terbaru</h2>
                    <div class="space-y-4">
                        <?php foreach($aktivitas as $aktif): 
                            $status_icon = $aktif['status'] == 'Dipinjam' ? 'fa-arrow-right text-yellow-400' : 
                                          ($aktif['status'] == 'Dikembalikan' ? 'fa-arrow-left text-green-400' : 'fa-exclamation-triangle text-red-400');
                            $status_text = $aktif['status'] == 'Dipinjam' ? 'dipinjam' : 
                                          ($aktif['status'] == 'Dikembalikan' ? 'dikembalikan' : 'terlambat');
                        ?>
                        <div class="flex items-start space-x-3 p-3 bg-gray-700 rounded-lg">
                            <div class="bg-gray-600 p-2 rounded-full">
                                <i class="fas <?= $status_icon ?>"></i>
                            </div>
                            <div>
                                <p class="font-medium">Buku "<?= $aktif['judul'] ?>" <?= $status_text ?></p>
                                <p class="text-gray-400 text-sm">Oleh: <?= $aktif['nama'] ?> - <?= date('d M Y', strtotime($aktif['tanggal_pinjam'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Books Table -->
            <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Daftar Buku Terbaru</h2>
                    <a href="tambah_buku.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Buku</span>
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Judul Buku</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Pengarang</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach($buku_terbaru as $buku): 
                                // Cek status buku
                                $status_buku = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_buku = ? AND status IN ('Dipinjam', 'Terlambat')");
                                $status_buku->execute([$buku['id_buku']]);
                                $dipinjam = $status_buku->fetchColumn();
                                $status = $dipinjam > 0 ? 'Dipinjam' : ($buku['stok'] > 0 ? 'Tersedia' : 'Habis');
                                $status_class = $status == 'Tersedia' ? 'bg-green-900 text-green-300' : 
                                              ($status == 'Dipinjam' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300');
                            ?>
                            <tr>
                                <td class="px-4 py-3"><?= $buku['judul'] ?></td>
                                <td class="px-4 py-3"><?= $buku['pengarang'] ?></td>
                                <td class="px-4 py-3"><?= $buku['nama_kategori'] ?></td>
                                <td class="px-4 py-3"><span class="<?= $status_class ?> text-xs px-2 py-1 rounded-full"><?= $status ?></span></td>
                                <td class="px-4 py-3">
                                    <a href="edit_buku.php?id=<?= $buku['id_buku'] ?>" class="text-blue-400 hover:text-blue-300 mr-2"><i class="fas fa-edit"></i></a>
                                    <a href="hapus_buku.php?id=<?= $buku['id_buku'] ?>" 
                                    class="text-red-400 hover:text-red-300"
                                    onclick="return hapusBuku(this);" 
                                    title="Hapus">
                                    <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart.js Implementation
        document.addEventListener('DOMContentLoaded', function() {
            // Chart Peminjaman Bulanan
            const ctx = document.getElementById('loanChart').getContext('2d');
            const loanChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_data['labels']) ?>,
                    datasets: [{
                        label: 'Peminjaman',
                        data: <?= json_encode($chart_data['peminjaman']) ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Pengembalian',
                        data: <?= json_encode($chart_data['pengembalian']) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            });
            
            // Chart Kategori Buku
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($kategori_data, 'nama_kategori')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($kategori_data, 'jumlah')) ?>,
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(236, 72, 153, 0.7)',
                            'rgba(14, 165, 233, 0.7)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(236, 72, 153, 1)',
                            'rgba(14, 165, 233, 1)'
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
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            });
            
            // Chart Status Peminjaman
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_column($status_data, 'status')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($status_data, 'jumlah')) ?>,
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.7)',  // Dipinjam - Kuning
                            'rgba(16, 185, 129, 0.7)',  // Dikembalikan - Hijau
                            'rgba(239, 68, 68, 0.7)'    // Terlambat - Merah
                        ],
                        borderColor: [
                            'rgba(245, 158, 11, 1)',
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
                                color: 'rgba(255, 255, 255, 0.7)'
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