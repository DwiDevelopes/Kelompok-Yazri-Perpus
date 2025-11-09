<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include QR Code library sederhana
require_once 'simpleqrcode.php';

// Buat folder qrcodes jika belum ada
if (!is_dir('qrcodes')) {
    mkdir('qrcodes', 0777, true);
}

// Generate QR Code untuk buku
if (isset($_POST['generate_qr'])) {
    $id_buku = $_POST['id_buku'];
    
    // Ambil data buku
    $buku = $pdo->prepare("SELECT * FROM buku WHERE id_buku = ?");
    $buku->execute([$id_buku]);
    $data_buku = $buku->fetch(PDO::FETCH_ASSOC);
    
    if ($data_buku) {
        // Data untuk QR Code
        $qr_data = "BUKU: " . $data_buku['judul'] . "\n" .
                  "Pengarang: " . $data_buku['pengarang'] . "\n" .
                  "ISBN: " . $data_buku['isbn'] . "\n" .
                  "Perpustakaan Digital";
        
        // Nama file QR Code
        $qr_file = 'qrcodes/buku_' . $data_buku['id_buku'] . '.png';
        
        try {
            // Generate QR Code menggunakan API external
            $qr_url = SimpleQRCode::generate($qr_data, $qr_file, 300);
            
            // Simpan ke database
            $kode_qr = 'BOOK_' . $data_buku['id_buku'] . '_' . time();
            $stmt = $pdo->prepare("INSERT INTO qr_codes (id_buku, kode_qr, tanggal_buat) VALUES (?, ?, CURDATE()) 
                                  ON DUPLICATE KEY UPDATE kode_qr = ?, tanggal_buat = CURDATE()");
            $stmt->execute([$id_buku, $kode_qr, $kode_qr]);
            
            $success = "QR Code berhasil digenerate untuk buku: " . $data_buku['judul'];
            $qr_generated = $qr_file;
        } catch (Exception $e) {
            $error = "Gagal generate QR Code: " . $e->getMessage();
        }
    }
}

// Hapus QR Code
if (isset($_GET['hapus_qr'])) {
    $id_qr = $_GET['hapus_qr'];
    
    $qr_data = $pdo->prepare("SELECT * FROM qr_codes WHERE id_qr = ?");
    $qr_data->execute([$id_qr]);
    $qr = $qr_data->fetch(PDO::FETCH_ASSOC);
    
    if ($qr) {
        $qr_file = 'qrcodes/buku_' . $qr['id_buku'] . '.png';
        if (file_exists($qr_file)) {
            unlink($qr_file);
        }
        
        $pdo->prepare("DELETE FROM qr_codes WHERE id_qr = ?")->execute([$id_qr]);
        $success = "QR Code berhasil dihapus";
    }
}

// Ambil semua buku
$buku = $pdo->query("SELECT * FROM buku ORDER BY judul")->fetchAll(PDO::FETCH_ASSOC);

// Ambil QR Codes yang sudah digenerate
$qr_codes = $pdo->query("
    SELECT q.*, b.judul, b.pengarang, b.isbn
    FROM qr_codes q 
    JOIN buku b ON q.id_buku = b.id_buku 
    ORDER BY q.tanggal_buat DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk chart
$total_buku = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$total_qr_generated = $pdo->query("SELECT COUNT(*) FROM qr_codes")->fetchColumn();
$qr_coverage = $total_buku > 0 ? round(($total_qr_generated / $total_buku) * 100, 1) : 0;

// Data QR Code per bulan
$qr_bulanan = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_buat, '%Y-%m') as bulan,
        COUNT(*) as jumlah
    FROM qr_codes 
    WHERE tanggal_buat >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tanggal_buat, '%Y-%m')
    ORDER BY bulan
")->fetchAll(PDO::FETCH_ASSOC);

// Data buku dengan QR Code terbanyak
$buku_qr_populer = $pdo->query("
    SELECT b.judul, COUNT(q.id_qr) as jumlah_qr
    FROM qr_codes q
    JOIN buku b ON q.id_buku = b.id_buku
    GROUP BY b.id_buku, b.judul
    ORDER BY jumlah_qr DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator QR Code - Perpustakaan Digital</title>
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
        .qr-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border: 1px solid #475569;
        }
        .qr-preview {
            background: white;
            padding: 10px;
            border-radius: 8px;
            display: inline-block;
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
                <h1 class="text-2xl font-bold">Generator QR Code</h1>
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
                            <p class="text-gray-400">Total Buku</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $total_buku ?></h3>
                        </div>
                        <div class="bg-blue-900 p-3 rounded-lg">
                            <i class="fas fa-book text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">QR Code Digenerate</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $total_qr_generated ?></h3>
                        </div>
                        <div class="bg-green-900 p-3 rounded-lg">
                            <i class="fas fa-qrcode text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400">Coverage QR Code</p>
                            <h3 class="text-3xl font-bold mt-2"><?= $qr_coverage ?>%</h3>
                        </div>
                        <div class="bg-purple-900 p-3 rounded-lg">
                            <i class="fas fa-chart-pie text-purple-400 text-xl"></i>
                        </div>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2 mt-4">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: <?= $qr_coverage ?>%"></div>
                    </div>
                </div>
            </div>

                        <!-- Form Generate QR Code -->
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4">Generate QR Code Baru</h2>
                <form method="POST" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 items-start md:items-end">
                    <div class="flex-1">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Pilih Buku</label>

                        <!-- Input pencarian -->
                        <input type="text" id="searchBuku" placeholder="Cari buku..." 
                            class="w-full px-3 py-2 mb-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white">

                        <!-- Select scrollable -->
                        <select name="id_buku" required size="8" 
                            class="w-full px-3 py-2 border border-gray-700 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white overflow-y-auto">
                            <?php foreach($buku as $b): ?>
                            <option value="<?= $b['id_buku'] ?>">
                                <?= htmlspecialchars($b['judul']) ?> - <?= $b['pengarang'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" name="generate_qr" 
                            class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-qrcode"></i>
                            <span>Generate QR Code</span>
                        </button>
                    </div>
                </form>
                <p class="text-gray-400 text-sm mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    QR Code akan berisi informasi buku yang dapat discan oleh anggota
                </p>
            </div>

            <script>
                // Filter select dengan input pencarian
                const searchInput = document.getElementById("searchBuku");
                const selectBuku = document.querySelector("select[name='id_buku']");

                searchInput.addEventListener("keyup", function () {
                    const filter = this.value.toLowerCase();
                    for (let option of selectBuku.options) {
                        if (option.value === "") continue; // skip placeholder
                        let text = option.text.toLowerCase();
                        option.style.display = text.includes(filter) ? "" : "none";
                    }
                });
            </script>

            
            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Chart QR Code Bulanan -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">QR Code Digenerate per Bulan</h2>
                    <div class="h-80">
                        <canvas id="qrBulananChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart Buku dengan QR Code -->
                <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4">Buku dengan QR Code Terbanyak</h2>
                    <div class="space-y-3">
                        <?php foreach($buku_qr_populer as $index => $buku): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-purple-900 rounded-full flex items-center justify-center">
                                    <span class="text-purple-400 font-bold text-sm"><?= $index + 1 ?></span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm"><?= htmlspecialchars($buku['judul']) ?></p>
                                    <p class="text-gray-400 text-xs"><?= $buku['jumlah_qr'] ?> QR Code</p>
                                </div>
                            </div>
                            <div class="bg-green-900 text-green-400 px-2 py-1 rounded-full text-xs">
                                <i class="fas fa-qrcode mr-1"></i>Active
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($buku_qr_populer)): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-qrcode text-2xl mb-2"></i>
                            <p>Belum ada QR Code yang digenerate</p>
                        </div>
                        <?php endif; ?>
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
            
            <!-- Preview QR Code -->
            <?php if (isset($qr_generated)): ?>
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4">QR Code Hasil Generate</h2>
                <div class="flex flex-col items-center">
                    <div class="qr-preview">
                        <img src="<?= $qr_generated ?>" alt="QR Code" class="w-48 h-48">
                    </div>
                    <p class="mt-4 text-gray-400">Scan QR code ini untuk informasi buku</p>
                    <div class="mt-4 flex space-x-2">
                        <a href="<?= $qr_generated ?>" download class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-download"></i>
                            <span>Download QR Code</span>
                        </a>
                        <a href="qrcode.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-times"></i>
                            <span>Tutup</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Daftar QR Code -->
            <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">QR Code Tersimpan (<?= count($qr_codes) ?>)</h2>
                    <div class="text-sm text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        Klik download untuk mendapatkan file QR Code
                    </div>
                </div>
                
                <?php if (empty($qr_codes)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-qrcode text-4xl mb-4"></i>
                    <p class="text-lg">Belum ada QR Code yang digenerate</p>
                    <p class="text-sm mt-2">Gunakan form di atas untuk membuat QR Code pertama Anda</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($qr_codes as $qr): 
                        $qr_file = 'qrcodes/buku_' . $qr['id_buku'] . '.png';
                    ?>
                    <div class="qr-card rounded-lg p-4 text-center">
                        <?php if (file_exists($qr_file)): ?>
                        <div class="qr-preview mx-auto mb-3">
                            <img src="<?= $qr_file ?>" alt="QR Code" class="w-24 h-24">
                        </div>
                        <?php else: ?>
                        <div class="w-24 h-24 bg-gray-700 flex items-center justify-center mx-auto mb-3 rounded">
                            <i class="fas fa-qrcode text-gray-400 text-xl"></i>
                        </div>
                        <?php endif; ?>
                        <h3 class="font-bold text-sm mb-1 truncate" title="<?= htmlspecialchars($qr['judul']) ?>">
                            <?= htmlspecialchars($qr['judul']) ?>
                        </h3>
                        <p class="text-xs text-gray-400 mb-1"><?= $qr['pengarang'] ?></p>
                        <p class="text-xs text-gray-500 mb-2">Kode: <?= $qr['kode_qr'] ?></p>
                        <p class="text-xs text-gray-500 mb-3">Dibuat: <?= date('d/m/Y', strtotime($qr['tanggal_buat'])) ?></p>
                        <div class="flex justify-center space-x-2">
                            <?php if (file_exists($qr_file)): ?>
                            <a href="<?= $qr_file ?>" download class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 flex items-center space-x-1 transition-colors">
                                <i class="fas fa-download text-xs"></i>
                                <span>Download</span>
                            </a>
                            <?php endif; ?>
                            <a href="qrcode.php?hapus_qr=<?= $qr['id_qr'] ?>" 
                               onclick="return hapusQRCode(this);"
                               class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 flex items-center space-x-1 transition-colors">
                                <i class="fas fa-trash text-xs"></i>
                                <span>Hapus</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Chart.js Implementation
        document.addEventListener('DOMContentLoaded', function() {
            // Chart QR Code Bulanan
            const qrBulananCtx = document.getElementById('qrBulananChart').getContext('2d');
            const qrBulananChart = new Chart(qrBulananCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($qr_bulanan, 'bulan')) ?>,
                    datasets: [{
                        label: 'QR Code Digenerate',
                        data: <?= json_encode(array_column($qr_bulanan, 'jumlah')) ?>,
                        backgroundColor: 'rgba(139, 92, 246, 0.7)',
                        borderColor: 'rgba(139, 92, 246, 1)',
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
        });
        
        // SweetAlert untuk konfirmasi hapus QR Code
        function hapusQRCode(el) {
            event.preventDefault();
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "QR Code akan dihapus permanen!",
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