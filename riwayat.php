<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Parameter filter
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$status = $_GET['status'] ?? '';

// Query riwayat
$query = "
    SELECT p.*, b.judul, b.pengarang, a.nama, a.email 
    FROM peminjaman p
    JOIN buku b ON p.id_buku = b.id_buku
    JOIN anggota a ON p.id_anggota = a.id_anggota
    WHERE 1=1
";

$params = [];

if (!empty($start_date)) {
    $query .= " AND p.tanggal_pinjam >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $query .= " AND p.tanggal_pinjam <= ?";
    $params[] = $end_date;
}

if (!empty($status)) {
    $query .= " AND p.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY p.tanggal_pinjam DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data untuk statistik dan chart
$total_peminjaman = $pdo->query("SELECT COUNT(*) FROM peminjaman")->fetchColumn();
$peminjaman_aktif = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status IN ('Dipinjam', 'Terlambat')")->fetchColumn();
$terlambat = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'Terlambat'")->fetchColumn();
$dikembalikan = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'Dikembalikan'")->fetchColumn();

// Data untuk chart status peminjaman
$status_data = [
    ['status' => 'Dikembalikan', 'jumlah' => $dikembalikan],
    ['status' => 'Dipinjam', 'jumlah' => $peminjaman_aktif - $terlambat],
    ['status' => 'Terlambat', 'jumlah' => $terlambat]
];

// Data untuk chart peminjaman bulanan
$peminjaman_bulanan = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_pinjam, '%Y-%m') as bulan,
        COUNT(*) as jumlah
    FROM peminjaman 
    WHERE tanggal_pinjam >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(tanggal_pinjam, '%Y-%m')
    ORDER BY bulan
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart anggota paling aktif
$anggota_aktif = $pdo->query("
    SELECT a.nama, COUNT(p.id_peminjaman) as jumlah_peminjaman
    FROM peminjaman p
    JOIN anggota a ON p.id_anggota = a.id_anggota
    WHERE p.tanggal_pinjam >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY a.id_anggota, a.nama
    ORDER BY jumlah_peminjaman DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart buku paling sering dipinjam dengan penanganan error
try {
    $buku_populer = $pdo->query("
        SELECT b.judul, COUNT(p.id_peminjaman) as jumlah_peminjaman
        FROM peminjaman p
        JOIN buku b ON p.id_buku = b.id_buku
        GROUP BY b.id_buku, b.judul
        ORDER BY jumlah_peminjaman DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Format judul buku agar tidak terlalu panjang untuk chart
    foreach($buku_populer as &$buku) {
        if(strlen($buku['judul']) > 30) {
            $buku['judul'] = substr($buku['judul'], 0, 30) . '...';
        }
    }
    unset($buku); // Hapus reference
    
} catch (PDOException $e) {
    $buku_populer = [];
    error_log("Error fetching popular books: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Perpustakaan Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h1 class="text-2xl font-bold">Riwayat Peminjaman</h1>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" placeholder="Cari..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-700 bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=Admin+Perpus&background=0D8ABC&color=fff" alt="Admin" class="w-10 h-10 rounded-full">
                        <span class="font-medium"><?= $_SESSION['admin_nama'] ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Total Peminjaman</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $total_peminjaman ?></h3>
                        </div>
                        <div class="bg-blue-900 p-3 rounded-lg">
                            <i class="fas fa-exchange-alt text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Sedang Dipinjam</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $peminjaman_aktif ?></h3>
                        </div>
                        <div class="bg-yellow-900 p-3 rounded-lg">
                            <i class="fas fa-book text-yellow-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Dikembalikan</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $dikembalikan ?></h3>
                        </div>
                        <div class="bg-green-900 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Terlambat</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $terlambat ?></h3>
                        </div>
                        <div class="bg-red-900 p-3 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Chart Status Peminjaman -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Distribusi Status Peminjaman</h2>
                    <div class="h-80">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart Peminjaman Bulanan -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Trend Peminjaman (12 Bulan)</h2>
                    <div class="h-80">
                        <canvas id="peminjamanChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Second Row Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Chart Buku Populer -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Buku Paling Sering Dipinjam</h2>
                    <div class="h-80">
                        <canvas id="bukuChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart Anggota Aktif -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Anggota Paling Aktif (6 Bulan)</h2>
                    <div class="space-y-3">
                        <?php foreach($anggota_aktif as $index => $anggota): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-900 rounded-full flex items-center justify-center">
                                    <span class="text-blue-400 font-bold text-sm"><?= $index + 1 ?></span>
                                </div>
                                <div>
                                    <p class="font-medium"><?= htmlspecialchars($anggota['nama']) ?></p>
                                    <p class="text-gray-400 text-sm"><?= $anggota['jumlah_peminjaman'] ?> peminjaman</p>
                                </div>
                            </div>
                            <div class="bg-green-900 text-green-400 px-3 py-1 rounded-full text-sm">
                                <i class="fas fa-star mr-1"></i>Aktif
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($anggota_aktif)): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-users text-2xl mb-2"></i>
                            <p>Belum ada data peminjaman dalam 6 bulan terakhir</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Filter -->
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4">Filter Riwayat</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-2">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" 
                            class="w-full px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-2">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" 
                            class="w-full px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white">
                            <option value="">Semua Status</option>
                            <option value="Dipinjam" <?= $status == 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                            <option value="Dikembalikan" <?= $status == 'Dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                            <option value="Terlambat" <?= $status == 'Terlambat' ? 'selected' : '' ?>>Terlambat</option>
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-filter"></i>
                            <span>Filter</span>
                        </button>
                        <a href="riwayat.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-refresh"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Tabel Riwayat -->
            <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Data Riwayat (<?= count($riwayat) ?>)</h2>
                    <div class="text-sm text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        Menampilkan semua riwayat peminjaman
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Buku</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Anggota</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tanggal Pinjam</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tanggal Kembali</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach($riwayat as $r): 
                                $status_class = $r['status'] == 'Dikembalikan' ? 'bg-green-900 text-green-300' : 
                                              ($r['status'] == 'Terlambat' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300');
                            ?>
                            <tr class="hover:bg-gray-700 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="bg-gray-700 text-gray-300 text-xs px-2 py-1 rounded-full">#<?= $r['id_peminjaman'] ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?= htmlspecialchars($r['judul']) ?></div>
                                    <div class="text-sm text-gray-400"><?= $r['pengarang'] ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?= htmlspecialchars($r['nama']) ?></div>
                                    <div class="text-sm text-gray-400"><?= $r['email'] ?></div>
                                </td>
                                <td class="px-4 py-3"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                <td class="px-4 py-3">
                                    <?= $r['tanggal_kembali'] ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '<span class="text-gray-400">-</span>' ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-3 py-1 rounded-full <?= $status_class ?>">
                                        <?= $r['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($riwayat)): ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-history text-4xl mb-4"></i>
                        <p class="text-lg">Tidak ada riwayat peminjaman</p>
                        <p class="text-sm mt-2">Coba ubah filter atau periode waktu</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart.js Implementation
        document.addEventListener('DOMContentLoaded', function() {
            // Chart Status Peminjaman
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Dikembalikan', 'Dipinjam', 'Terlambat'],
                    datasets: [{
                        data: [<?= $dikembalikan ?>, <?= $peminjaman_aktif - $terlambat ?>, <?= $terlambat ?>],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(239, 68, 68, 0.7)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
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
            
            // Chart Peminjaman Bulanan
            const peminjamanCtx = document.getElementById('peminjamanChart').getContext('2d');
            const peminjamanChart = new Chart(peminjamanCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($peminjaman_bulanan, 'bulan')) ?>,
                    datasets: [{
                        label: 'Jumlah Peminjaman',
                        data: <?= json_encode(array_column($peminjaman_bulanan, 'jumlah')) ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
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
            
            // Chart Buku Populer - dengan penanganan data kosong
            const bukuCtx = document.getElementById('bukuChart').getContext('2d');
            const bukuLabels = <?= !empty($buku_populer) ? json_encode(array_column($buku_populer, 'judul')) : '[]' ?>;
            const bukuData = <?= !empty($buku_populer) ? json_encode(array_column($buku_populer, 'jumlah_peminjaman')) : '[]' ?>;

            const bukuChart = new Chart(bukuCtx, {
                type: 'bar',
                data: {
                    labels: bukuLabels,
                    datasets: [{
                        label: 'Jumlah Peminjaman',
                        data: bukuData,
                        backgroundColor: 'rgba(139, 92, 246, 0.7)',
                        borderColor: 'rgba(139, 92, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        y: {
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
        });
    </script>
</body>
</html>