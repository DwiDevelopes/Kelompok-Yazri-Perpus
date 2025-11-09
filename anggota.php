<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Proses tambah anggota
if (isset($_POST['tambah_anggota'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];
    
    $stmt = $pdo->prepare("INSERT INTO anggota (nama, email, telepon, alamat, tanggal_daftar) VALUES (?, ?, ?, ?, CURDATE())");
    
    if ($stmt->execute([$nama, $email, $telepon, $alamat])) {
        $success = "Anggota berhasil ditambahkan";
    } else {
        $error = "Gagal menambahkan anggota. Email mungkin sudah terdaftar.";
    }
}

// Proses update status anggota
if (isset($_POST['update_status'])) {
    $id_anggota = $_POST['id_anggota'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE anggota SET status = ? WHERE id_anggota = ?");
    $stmt->execute([$status, $id_anggota]);
    $success = "Status anggota berhasil diupdate";
}

// Ambil data anggota
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM anggota WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (nama LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY nama";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$anggota = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart
$total_anggota = $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn();
$anggota_aktif = $pdo->query("SELECT COUNT(*) FROM anggota WHERE status = 'Aktif'")->fetchColumn();
$anggota_nonaktif = $pdo->query("SELECT COUNT(*) FROM anggota WHERE status = 'Nonaktif'")->fetchColumn();

// Data pendaftaran bulanan
$pendaftaran_bulanan = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_daftar, '%Y-%m') as bulan,
        COUNT(*) as jumlah
    FROM anggota 
    WHERE tanggal_daftar >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(tanggal_daftar, '%Y-%m')
    ORDER BY bulan
")->fetchAll(PDO::FETCH_ASSOC);

// Data anggota per status
$status_data = [
    ['status' => 'Aktif', 'jumlah' => $anggota_aktif],
    ['status' => 'Nonaktif', 'jumlah' => $anggota_nonaktif]
];

// Data untuk chart usia keanggotaan
$usia_anggota = $pdo->query("
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(MONTH, tanggal_daftar, NOW()) < 6 THEN '0-6 Bulan'
            WHEN TIMESTAMPDIFF(MONTH, tanggal_daftar, NOW()) < 12 THEN '6-12 Bulan'
            WHEN TIMESTAMPDIFF(MONTH, tanggal_daftar, NOW()) < 24 THEN '1-2 Tahun'
            ELSE '> 2 Tahun'
        END as kelompok,
        COUNT(*) as jumlah
    FROM anggota 
    GROUP BY kelompok
    ORDER BY FIELD(kelompok, '0-6 Bulan', '6-12 Bulan', '1-2 Tahun', '> 2 Tahun')
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart pertumbuhan anggota
$pertumbuhan_anggota = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_daftar, '%Y-%m') as bulan,
        COUNT(*) as jumlah,
        (SELECT COUNT(*) FROM anggota a2 WHERE DATE_FORMAT(a2.tanggal_daftar, '%Y-%m') <= bulan) as total_kumulatif
    FROM anggota 
    WHERE tanggal_daftar >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(tanggal_daftar, '%Y-%m')
    ORDER BY bulan
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart distribusi anggota berdasarkan alamat (kota)
$distribusi_alamat = $pdo->query("
    SELECT 
        CASE 
            WHEN alamat LIKE '%jakarta%' OR alamat LIKE '%Jakarta%' THEN 'Jakarta'
            WHEN alamat LIKE '%bandung%' OR alamat LIKE '%Bandung%' THEN 'Bandung'
            WHEN alamat LIKE '%surabaya%' OR alamat LIKE '%Surabaya%' THEN 'Surabaya'
            WHEN alamat LIKE '%yogyakarta%' OR alamat LIKE '%Yogyakarta%' THEN 'Yogyakarta'
            WHEN alamat LIKE '%semarang%' OR alamat LIKE '%Semarang%' THEN 'Semarang'
            WHEN alamat LIKE '%medan%' OR alamat LIKE '%Medan%' THEN 'Medan'
            WHEN alamat LIKE '%makassar%' OR alamat LIKE '%Makassar%' THEN 'Makassar'
            WHEN alamat LIKE '%bali%' OR alamat LIKE '%Bali%' OR alamat LIKE '%denpasar%' THEN 'Bali'
            ELSE 'Lainnya'
        END as kota,
        COUNT(*) as jumlah
    FROM anggota 
    WHERE alamat IS NOT NULL AND alamat != ''
    GROUP BY kota
    ORDER BY jumlah DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart anggota aktif vs nonaktif per bulan
$status_bulanan = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_daftar, '%Y-%m') as bulan,
        SUM(CASE WHEN status = 'Aktif' THEN 1 ELSE 0 END) as aktif,
        SUM(CASE WHEN status = 'Nonaktif' THEN 1 ELSE 0 END) as nonaktif
    FROM anggota 
    WHERE tanggal_daftar >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tanggal_daftar, '%Y-%m')
    ORDER BY bulan
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Anggota - Perpustakaan Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="flex">
        <div class="w-64 bg-gray-800 text-gray-200 min-h-screen p-4">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold">Manajemen Anggota</h1>
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Total Anggota</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $total_anggota ?></h3>
                        </div>
                        <div class="bg-blue-900 p-3 rounded-lg">
                            <i class="fas fa-users text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Anggota Aktif</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $anggota_aktif ?></h3>
                        </div>
                        <div class="bg-green-900 p-3 rounded-lg">
                            <i class="fas fa-user-check text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Anggota Nonaktif</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $anggota_nonaktif ?></h3>
                        </div>
                        <div class="bg-red-900 p-3 rounded-lg">
                            <i class="fas fa-user-times text-red-400 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section - Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Chart Status Anggota -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Status Keanggotaan</h2>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart Pendaftaran Bulanan -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Pendaftaran Bulanan (12 Bulan)</h2>
                    <div class="chart-container">
                        <canvas id="pendaftaranChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section - Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Chart Usia Keanggotaan -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Usia Keanggotaan</h2>
                    <div class="chart-container">
                        <canvas id="usiaChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart Distribusi Geografis -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Distribusi Geografis</h2>
                    <div class="chart-container">
                        <canvas id="distribusiChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section - Row 3 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Chart Pertumbuhan Kumulatif -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Pertumbuhan Anggota</h2>
                    <div class="chart-container">
                        <canvas id="pertumbuhanChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart Status Bulanan -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Status Anggota per Bulan</h2>
                    <div class="chart-container">
                        <canvas id="statusBulananChart"></canvas>
                    </div>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
            <div class="bg-green-900 border border-green-700 text-green-300 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i><?= $success ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-900 border border-red-700 text-red-300 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
            </div>
            <?php endif; ?>
            
            <!-- Form Tambah Anggota -->
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4">Tambah Anggota Baru</h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-2">Nama Lengkap *</label>
                        <input type="text" name="nama" required class="w-full px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-2">Email *</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-2">Telepon</label>
                        <input type="text" name="telepon" class="w-full px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Alamat</label>
                        <textarea name="alamat" rows="2" class="w-full px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" name="tambah_anggota" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-plus"></i>
                            <span>Tambah Anggota</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Pencarian -->
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-6 border border-gray-700">
                <form method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                    <input type="text" name="search" placeholder="Cari nama atau email..." 
                        value="<?= htmlspecialchars($search) ?>" 
                        class="flex-1 px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white">
                    <div class="flex space-x-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-search"></i>
                            <span>Cari</span>
                        </button>
                        <a href="anggota.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-refresh"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Tabel Anggota -->
            <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Daftar Anggota (<?= count($anggota) ?>)</h2>
                    <div class="text-sm text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        Klik status untuk mengubah
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Telepon</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tanggal Daftar</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach($anggota as $a): ?>
                            <tr class="hover:bg-gray-700 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="bg-gray-700 text-gray-300 text-xs px-2 py-1 rounded-full">#<?= $a['id_anggota'] ?></span>
                                </td>
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($a['nama']) ?></td>
                                <td class="px-4 py-3"><?= $a['email'] ?></td>
                                <td class="px-4 py-3"><?= $a['telepon'] ?: '-' ?></td>
                                <td class="px-4 py-3"><?= date('d/m/Y', strtotime($a['tanggal_daftar'])) ?></td>
                                <td class="px-4 py-3">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="id_anggota" value="<?= $a['id_anggota'] ?>">
                                        <select name="status" onchange="this.form.submit()" 
                                            class="text-xs px-3 py-1 rounded border-0 focus:ring-2 focus:ring-blue-500 <?= $a['status'] == 'Aktif' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' ?>">
                                            <option value="Aktif" <?= $a['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                            <option value="Nonaktif" <?= $a['status'] == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <a href="edit_anggota.php?id=<?= $a['id_anggota'] ?>" class="text-blue-400 hover:text-blue-300 p-2 rounded-lg hover:bg-gray-600 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="detail_anggota.php?id=<?= $a['id_anggota'] ?>" class="text-green-400 hover:text-green-300 p-2 rounded-lg hover:bg-gray-600 transition-colors" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($anggota)): ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p class="text-lg">Tidak ada anggota yang ditemukan</p>
                        <p class="text-sm mt-2">Coba ubah pencarian atau tambahkan anggota baru</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart.js Implementation
        document.addEventListener('DOMContentLoaded', function() {
            const chartColors = {
                blue: 'rgba(59, 130, 246, 0.7)',
                green: 'rgba(16, 185, 129, 0.7)',
                red: 'rgba(239, 68, 68, 0.7)',
                yellow: 'rgba(245, 158, 11, 0.7)',
                purple: 'rgba(139, 92, 246, 0.7)',
                indigo: 'rgba(99, 102, 241, 0.7)',
                pink: 'rgba(236, 72, 153, 0.7)',
                teal: 'rgba(20, 184, 166, 0.7)'
            };

            const borderColors = {
                blue: 'rgba(59, 130, 246, 1)',
                green: 'rgba(16, 185, 129, 1)',
                red: 'rgba(239, 68, 68, 1)',
                yellow: 'rgba(245, 158, 11, 1)',
                purple: 'rgba(139, 92, 246, 1)',
                indigo: 'rgba(99, 102, 241, 1)',
                pink: 'rgba(236, 72, 153, 1)',
                teal: 'rgba(20, 184, 166, 1)'
            };

            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                },
                scales: {
                    y: {
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
                }
            };

            // Chart Status Anggota
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Aktif', 'Nonaktif'],
                    datasets: [{
                        data: [<?= $anggota_aktif ?>, <?= $anggota_nonaktif ?>],
                        backgroundColor: [chartColors.green, chartColors.red],
                        borderColor: [borderColors.green, borderColors.red],
                        borderWidth: 2
                    }]
                },
                options: {
                    ...chartOptions,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((context.parsed / total) * 100);
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Chart Pendaftaran Bulanan
            const pendaftaranCtx = document.getElementById('pendaftaranChart').getContext('2d');
            new Chart(pendaftaranCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($pendaftaran_bulanan, 'bulan')) ?>,
                    datasets: [{
                        label: 'Jumlah Pendaftaran',
                        data: <?= json_encode(array_column($pendaftaran_bulanan, 'jumlah')) ?>,
                        backgroundColor: chartColors.blue,
                        borderColor: borderColors.blue,
                        borderWidth: 1
                    }]
                },
                options: chartOptions
            });
            
            // Chart Usia Keanggotaan
            const usiaCtx = document.getElementById('usiaChart').getContext('2d');
            new Chart(usiaCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_column($usia_anggota, 'kelompok')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($usia_anggota, 'jumlah')) ?>,
                        backgroundColor: [
                            chartColors.blue,
                            chartColors.green,
                            chartColors.yellow,
                            chartColors.purple
                        ],
                        borderColor: [
                            borderColors.blue,
                            borderColors.green,
                            borderColors.yellow,
                            borderColors.purple
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    ...chartOptions,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            });
            
            // Chart Distribusi Geografis
            const distribusiCtx = document.getElementById('distribusiChart').getContext('2d');
            new Chart(distribusiCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($distribusi_alamat, 'kota')) ?>,
                    datasets: [{
                        label: 'Jumlah Anggota',
                        data: <?= json_encode(array_column($distribusi_alamat, 'jumlah')) ?>,
                        backgroundColor: [
                            chartColors.blue,
                            chartColors.green,
                            chartColors.yellow,
                            chartColors.purple,
                            chartColors.indigo,
                            chartColors.pink,
                            chartColors.teal,
                            chartColors.red
                        ],
                        borderColor: [
                            borderColors.blue,
                            borderColors.green,
                            borderColors.yellow,
                            borderColors.purple,
                            borderColors.indigo,
                            borderColors.pink,
                            borderColors.teal,
                            borderColors.red
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    ...chartOptions,
                    indexAxis: 'y'
                }
            });
            
            // Chart Pertumbuhan Kumulatif
            const pertumbuhanCtx = document.getElementById('pertumbuhanChart').getContext('2d');
            new Chart(pertumbuhanCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($pertumbuhan_anggota, 'bulan')) ?>,
                    datasets: [{
                        label: 'Total Anggota Kumulatif',
                        data: <?= json_encode(array_column($pertumbuhan_anggota, 'total_kumulatif')) ?>,
                        backgroundColor: chartColors.green,
                        borderColor: borderColors.green,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: chartOptions
            });
            
            // Chart Status Bulanan
            const statusBulananCtx = document.getElementById('statusBulananChart').getContext('2d');
            new Chart(statusBulananCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($status_bulanan, 'bulan')) ?>,
                    datasets: [
                        {
                            label: 'Aktif',
                            data: <?= json_encode(array_column($status_bulanan, 'aktif')) ?>,
                            backgroundColor: chartColors.green,
                            borderColor: borderColors.green,
                            borderWidth: 1
                        },
                        {
                            label: 'Nonaktif',
                            data: <?= json_encode(array_column($status_bulanan, 'nonaktif')) ?>,
                            backgroundColor: chartColors.red,
                            borderColor: borderColors.red,
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        x: {
                            stacked: false,
                        },
                        y: {
                            stacked: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>