<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Proses peminjaman baru
if (isset($_POST['tambah_peminjaman'])) {
    $id_buku = $_POST['id_buku'];
    $id_anggota = $_POST['id_anggota'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    
    // Cek stok buku
    $stok_stmt = $pdo->prepare("SELECT stok FROM buku WHERE id_buku = ?");
    $stok_stmt->execute([$id_buku]);
    $stok = $stok_stmt->fetchColumn();
    
    // Cek buku yang sedang dipinjam
    $dipinjam_stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_buku = ? AND status IN ('Dipinjam', 'Terlambat')");
    $dipinjam_stmt->execute([$id_buku]);
    $dipinjam = $dipinjam_stmt->fetchColumn();
    
    if ($dipinjam >= $stok) {
        $error = "Buku tidak tersedia untuk dipinjam";
    } else {
        $stmt = $pdo->prepare("INSERT INTO peminjaman (id_buku, id_anggota, tanggal_pinjam, tanggal_kembali, status) VALUES (?, ?, CURDATE(), ?, 'Dipinjam')");
        if ($stmt->execute([$id_buku, $id_anggota, $tanggal_kembali])) {
            $success = "Peminjaman berhasil dicatat";
        } else {
            $error = "Gagal mencatat peminjaman";
        }
    }
}

// Proses pengembalian
if (isset($_POST['kembalikan_buku'])) {
    $id_peminjaman = $_POST['id_peminjaman'];
    
    $stmt = $pdo->prepare("UPDATE peminjaman SET status = 'Dikembalikan', tanggal_kembali = CURDATE() WHERE id_peminjaman = ?");
    if ($stmt->execute([$id_peminjaman])) {
        $success = "Buku berhasil dikembalikan";
    } else {
        $error = "Gagal mengembalikan buku";
    }
}

// Ambil data peminjaman aktif
$peminjaman = $pdo->query("
    SELECT p.*, b.judul, a.nama, a.email 
    FROM peminjaman p
    JOIN buku b ON p.id_buku = b.id_buku
    JOIN anggota a ON p.id_anggota = a.id_anggota
    WHERE p.status IN ('Dipinjam', 'Terlambat')
    ORDER BY p.tanggal_pinjam DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil data untuk dropdown
$buku_tersedia = $pdo->query("
    SELECT b.*, (b.stok - COALESCE((
        SELECT COUNT(*) FROM peminjaman p 
        WHERE p.id_buku = b.id_buku AND p.status IN ('Dipinjam', 'Terlambat')
    ), 0)) as tersedia
    FROM buku b
    HAVING tersedia > 0
")->fetchAll(PDO::FETCH_ASSOC);

$anggota_aktif = $pdo->query("SELECT * FROM anggota WHERE status = 'Aktif'")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart
$total_peminjaman_aktif = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status IN ('Dipinjam', 'Terlambat')")->fetchColumn();
$peminjaman_terlambat = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'Terlambat' OR (status = 'Dipinjam' AND tanggal_kembali < CURDATE())")->fetchColumn();
$peminjaman_tepat_waktu = $total_peminjaman_aktif - $peminjaman_terlambat;

// Data peminjaman bulanan
$peminjaman_bulanan = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_pinjam, '%Y-%m') as bulan,
        COUNT(*) as jumlah
    FROM peminjaman 
    WHERE tanggal_pinjam >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tanggal_pinjam, '%Y-%m')
    ORDER BY bulan
")->fetchAll(PDO::FETCH_ASSOC);

// Data buku paling sering dipinjam
$buku_populer = $pdo->query("
    SELECT b.judul, COUNT(p.id_peminjaman) as jumlah_peminjaman
    FROM peminjaman p
    JOIN buku b ON p.id_buku = b.id_buku
    WHERE p.tanggal_pinjam >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    GROUP BY b.id_buku, b.judul
    ORDER BY jumlah_peminjaman DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Data status peminjaman
$status_peminjaman = [
    ['status' => 'Tepat Waktu', 'jumlah' => $peminjaman_tepat_waktu],
    ['status' => 'Terlambat', 'jumlah' => $peminjaman_terlambat]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Peminjaman - Perpustakaan Digital</title>
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
                <h1 class="text-2xl font-bold">Manajemen Peminjaman</h1>
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
                            <p class="text-gray-400">Peminjaman Aktif</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $total_peminjaman_aktif ?></h3>
                        </div>
                        <div class="bg-blue-900 p-3 rounded-lg">
                            <i class="fas fa-exchange-alt text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Tepat Waktu</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $peminjaman_tepat_waktu ?></h3>
                        </div>
                        <div class="bg-green-900 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Keterlambatan</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $peminjaman_terlambat ?></h3>
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
                    <h2 class="text-xl font-bold mb-4">Status Peminjaman Aktif</h2>
                    <div class="h-80">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart Peminjaman Bulanan -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Trend Peminjaman (6 Bulan)</h2>
                    <div class="h-80">
                        <canvas id="peminjamanChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Buku Populer -->
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-8 border border-gray-700">
                <h2 class="text-xl font-bold mb-4">Buku Paling Populer (3 Bulan Terakhir)</h2>
                <div class="space-y-3">
                    <?php foreach($buku_populer as $index => $buku): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-blue-400 font-bold text-sm"><?= $index + 1 ?></span>
                            </div>
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($buku['judul']) ?></p>
                                <p class="text-gray-400 text-sm"><?= $buku['jumlah_peminjaman'] ?> kali dipinjam</p>
                            </div>
                        </div>
                        <div class="bg-blue-900 text-blue-400 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-fire mr-1"></i>Populer
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($buku_populer)): ?>
                    <div class="text-center py-4 text-gray-400">
                        <i class="fas fa-book text-2xl mb-2"></i>
                        <p>Belum ada data peminjaman dalam 3 bulan terakhir</p>
                    </div>
                    <?php endif; ?>
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

<!-- jQuery (dibutuhkan oleh Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS dan JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Styling Dark Theme -->
<style>

/* ====== Select2 Dark Theme ====== */
.select2-container--default .select2-selection--single {
    background-color: #1f2937; /* bg-gray-800 */
    border: 1px solid #374151; /* border-gray-700 */
    color: #f9fafb;
    height: 42px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection__rendered {
    color: #f9fafb;
    line-height: 42px;
    padding-left: 12px;
}
.select2-container--default .select2-selection__placeholder {
    color: #9ca3af; /* text-gray-400 */
}
.select2-container--default .select2-selection__arrow b {
    border-color: #f9fafb transparent transparent transparent;
}
.select2-dropdown {
    background-color: #1f2937; /* dropdown bg */
    border: 1px solid #374151;
}
.select2-results__option {
    color: #f9fafb;
    padding: 8px 12px;
}
.select2-results__option--highlighted {
    background-color: #2563eb; /* blue-600 */
    color: white;
}

/* ====== Input dan Label ====== */
label {
    color: #d1d5db; /* text-gray-300 */
}
input, select {
    background-color: #1f2937 !important;
    color: #f9fafb !important;
    border: 1px solid #374151 !important;
    border-radius: 0.5rem;
}
input:focus, select:focus {
    outline: none;
    border-color: #3b82f6; /* blue-500 */
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

/* ====== Tombol ====== */
button {
    background-color: #2563eb; /* blue-600 */
    color: white;
    transition: background-color 0.3s ease;
}
button:hover {
    background-color: #1d4ed8; /* blue-700 */
}

/* ====== Card ====== */
.form-card {
    background-color: #1f2937; /* bg-gray-800 */
    border: 1px solid #374151;
    border-radius: 0.75rem;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    padding: 1.5rem;
}
</style>

<!-- Form Peminjaman Baru -->
<div class="form-card mb-6">
    <h2 class="text-xl font-bold mb-4 text-white">Peminjaman Baru</h2>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Pilih Buku -->
        <div>
            <label class="block text-sm font-bold mb-2">Buku *</label>
            <select id="select-buku" name="id_buku" required class="w-full">
                <option value="">Pilih Buku</option>
                <?php foreach($buku_tersedia as $b): ?>
                    <option value="<?= $b['id_buku'] ?>">
                        <?= htmlspecialchars($b['judul']) ?> (Tersedia: <?= $b['tersedia'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Pilih Anggota -->
        <div>
            <label class="block text-sm font-bold mb-2">Anggota *</label>
            <select id="select-anggota" name="id_anggota" required class="w-full">
                <option value="">Pilih Anggota</option>
                <?php foreach($anggota_aktif as $a): ?>
                    <option value="<?= $a['id_anggota'] ?>"><?= htmlspecialchars($a['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tanggal Kembali -->
        <div>
            <label class="block text-sm font-bold mb-2">Tanggal Kembali *</label>
            <input type="date" name="tanggal_kembali" required 
                min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                class="w-full px-3 py-2">
        </div>

        <!-- Tombol Submit -->
        <div class="md:col-span-3">
            <button type="submit" name="tambah_peminjaman" 
                class="w-full md:w-auto px-6 py-3 rounded-lg flex items-center justify-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Catat Peminjaman</span>
            </button>
        </div>
    </form>
</div>

<!-- Inisialisasi Select2 -->
<script>
$(document).ready(function() {
    $('#select-buku').select2({
        placeholder: "Cari atau pilih buku...",
        allowClear: true,
        width: '100%',
        dropdownAutoWidth: true
    });
    $('#select-anggota').select2({
        placeholder: "Cari atau pilih anggota...",
        allowClear: true,
        width: '100%',
        dropdownAutoWidth: true
    });
});
</script>


            
            <!-- Daftar Peminjaman Aktif -->
            <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Peminjaman Aktif (<?= count($peminjaman) ?>)</h2>
                    <div class="text-sm text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        Klik tombol "Kembalikan" untuk menandai buku telah dikembalikan
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Batas Kembali</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach($peminjaman as $p): 
                                $terlambat = strtotime($p['tanggal_kembali']) < time() && $p['status'] == 'Dipinjam';
                                $status_class = $terlambat ? 'bg-red-900 text-red-300' : 
                                              ($p['status'] == 'Terlambat' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300');
                            ?>
                            <tr class="hover:bg-gray-700 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="bg-gray-700 text-gray-300 text-xs px-2 py-1 rounded-full">#<?= $p['id_peminjaman'] ?></span>
                                </td>
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($p['judul']) ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?= htmlspecialchars($p['nama']) ?></div>
                                    <div class="text-sm text-gray-400"><?= $p['email'] ?></div>
                                </td>
                                <td class="px-4 py-3"><?= date('d/m/Y', strtotime($p['tanggal_pinjam'])) ?></td>
                                <td class="px-4 py-3 <?= $terlambat ? 'text-red-400 font-bold' : '' ?>">
                                    <?= date('d/m/Y', strtotime($p['tanggal_kembali'])) ?>
                                    <?php if ($terlambat): ?>
                                    <br><span class="text-xs text-red-400">(Terlambat)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-3 py-1 rounded-full <?= $status_class ?>">
                                        <?= $terlambat ? 'Terlambat' : $p['status'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman'] ?>">
                                        <button type="submit" name="kembalikan_buku" 
                                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center space-x-2 transition-colors">
                                            <i class="fas fa-undo"></i>
                                            <span>Kembalikan</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($peminjaman)): ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-exchange-alt text-4xl mb-4"></i>
                        <p class="text-lg">Tidak ada peminjaman aktif</p>
                        <p class="text-sm mt-2">Semua buku telah dikembalikan</p>
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
                    labels: ['Tepat Waktu', 'Terlambat'],
                    datasets: [{
                        data: [<?= $peminjaman_tepat_waktu ?>, <?= $peminjaman_terlambat ?>],
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
            
            // Chart Peminjaman Bulanan
            const peminjamanCtx = document.getElementById('peminjamanChart').getContext('2d');
            const peminjamanChart = new Chart(peminjamanCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($peminjaman_bulanan, 'bulan')) ?>,
                    datasets: [{
                        label: 'Jumlah Peminjaman',
                        data: <?= json_encode(array_column($peminjaman_bulanan, 'jumlah')) ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
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
        });
    </script>
</body>
</html>