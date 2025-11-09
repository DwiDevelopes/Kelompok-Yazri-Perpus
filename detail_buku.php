<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id_buku = $_GET['id'] ?? 0;

// Ambil data buku
$buku = $pdo->prepare("
    SELECT b.*, k.nama_kategori 
    FROM buku b 
    JOIN kategori k ON b.id_kategori = k.id_kategori 
    WHERE b.id_buku = ?
");
$buku->execute([$id_buku]);
$data_buku = $buku->fetch(PDO::FETCH_ASSOC);

if (!$data_buku) {
    header('Location: koleksi_buku.php');
    exit;
}

// Ambil riwayat peminjaman buku
$riwayat_peminjaman = $pdo->prepare("
    SELECT p.*, a.nama, a.email 
    FROM peminjaman p 
    JOIN anggota a ON p.id_anggota = a.id_anggota 
    WHERE p.id_buku = ? 
    ORDER BY p.tanggal_pinjam DESC
    LIMIT 10
");
$riwayat_peminjaman->execute([$id_buku]);
$riwayat = $riwayat_peminjaman->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$total_peminjaman = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_buku = ?");
$total_peminjaman->execute([$id_buku]);
$total_pinjam = $total_peminjaman->fetchColumn();

$sedang_dipinjam = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_buku = ? AND status IN ('Dipinjam', 'Terlambat')");
$sedang_dipinjam->execute([$id_buku]);
$dipinjam = $sedang_dipinjam->fetchColumn();

$tersedia = $data_buku['stok'] - $dipinjam;
$status = $tersedia > 0 ? 'Tersedia' : 'Habis';
$status_class = $status == 'Tersedia' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Buku - Perpustakaan Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom scrollbar untuk dark mode */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #1f2937;
        }
        ::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="flex">
        <div class="w-64 bg-gray-800 text-gray-100 min-h-screen p-4">
            <?php include 'sidebar.php'; ?>
        </div>
    <link rel="icon" type="image/x-icon" href="icon.png">
        
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-white">Detail Buku</h1>
                <div class="flex space-x-2">
                    <a href="edit_buku.php?id=<?= $data_buku['id_buku'] ?>" 
                       class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-600 flex items-center space-x-2 transition-colors">
                        <i class="fas fa-edit"></i>
                        <span>Edit</span>
                    </a>
                    <a href="koleksi_buku.php" class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center space-x-2 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Informasi Buku -->
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 lg:col-span-2 border border-gray-700">
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Cover Buku -->
                        <div class="flex-shrink-0">
                            <?php if ($data_buku['gambar']): ?>
                            <img src="<?= $data_buku['gambar'] ?>" alt="Cover Buku" 
                                class="w-48 h-64 object-cover rounded-lg border border-gray-700 shadow-md">
                            <?php else: ?>
                            <div class="w-48 h-64 bg-gray-700 rounded-lg flex items-center justify-center border border-gray-600 shadow-md">
                                <i class="fas fa-book text-gray-400 text-4xl"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Detail Buku -->
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-white mb-2"><?= htmlspecialchars($data_buku['judul']) ?></h2>
                            <p class="text-gray-300 mb-4">oleh <span class="font-semibold"><?= htmlspecialchars($data_buku['pengarang']) ?></span></p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label class="block text-gray-400 text-sm font-bold mb-1">Kategori</label>
                                    <p class="text-gray-100"><?= $data_buku['nama_kategori'] ?></p>
                                </div>
                                <div>
                                    <label class="block text-gray-400 text-sm font-bold mb-1">ISBN</label>
                                    <p class="text-gray-100"><?= $data_buku['isbn'] ?: '-' ?></p>
                                </div>
                                <div>
                                    <label class="block text-gray-400 text-sm font-bold mb-1">Tahun Terbit</label>
                                    <p class="text-gray-100"><?= $data_buku['tahun_terbit'] ?: '-' ?></p>
                                </div>
                                <div>
                                    <label class="block text-gray-400 text-sm font-bold mb-1">Penerbit</label>
                                    <p class="text-gray-100"><?= $data_buku['penerbit'] ?: '-' ?></p>
                                </div>
                                <div>
                                    <label class="block text-gray-400 text-sm font-bold mb-1">Stok Total</label>
                                    <p class="text-gray-100"><?= $data_buku['stok'] ?> eksemplar</p>
                                </div>
                                <div>
                                    <label class="block text-gray-400 text-sm font-bold mb-1">Status</label>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_class ?>">
                                        <?= $status ?> (<?= $tersedia ?>)
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($data_buku['deskripsi']): ?>
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">Deskripsi</label>
                                <p class="text-gray-100 leading-relaxed"><?= nl2br(htmlspecialchars($data_buku['deskripsi'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Statistik & QR Code -->
                <div class="space-y-6">
                    <!-- Statistik -->
                    <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                        <h3 class="text-lg font-bold mb-4 text-white">Statistik</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300">Total Dipinjam</span>
                                <span class="font-bold text-blue-400"><?= $total_pinjam ?>x</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300">Sedang Dipinjam</span>
                                <span class="font-bold text-yellow-400"><?= $dipinjam ?> eks</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300">Tersedia</span>
                                <span class="font-bold text-green-400"><?= $tersedia ?> eks</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- QR Code -->
                    <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                        <h3 class="text-lg font-bold mb-4 text-white">QR Code</h3>
                        <?php
                        $qr_file = 'qrcodes/buku_' . $data_buku['id_buku'] . '.png';
                        if (file_exists($qr_file)):
                        ?>
                        <div class="text-center">
                            <img src="<?= $qr_file ?>" alt="QR Code" class="w-32 h-32 mx-auto mb-2 bg-white p-2 rounded">
                            <a href="<?= $qr_file ?>" download 
                               class="bg-blue-700 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 inline-block transition-colors">
                                <i class="fas fa-download mr-1"></i>Download QR
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-gray-400">
                            <i class="fas fa-qrcode text-3xl mb-2"></i>
                            <p class="text-sm">Belum ada QR Code</p>
                            <a href="qrcode.php" class="text-blue-400 hover:text-blue-300 text-sm transition-colors">Generate QR Code</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Riwayat Peminjaman Terbaru -->
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 mt-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4 text-white">Riwayat Peminjaman Terbaru</h2>
                
                <?php if (empty($riwayat)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-history text-4xl mb-4"></i>
                    <p>Belum ada riwayat peminjaman</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Anggota</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tanggal Pinjam</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Batas Kembali</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach($riwayat as $r): 
                                $terlambat = strtotime($r['tanggal_kembali']) < time() && $r['status'] == 'Dipinjam';
                                $status_class = $r['status'] == 'Dikembalikan' ? 'bg-green-900 text-green-300' : 
                                              ($terlambat || $r['status'] == 'Terlambat' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300');
                            ?>
                            <tr class="hover:bg-gray-750 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-white"><?= htmlspecialchars($r['nama']) ?></div>
                                    <div class="text-sm text-gray-400"><?= $r['email'] ?></div>
                                </td>
                                <td class="px-4 py-3 text-gray-300"><?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                <td class="px-4 py-3 <?= $terlambat ? 'text-red-400 font-bold' : 'text-gray-300' ?>">
                                    <?= $r['tanggal_kembali'] ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '-' ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_class ?>">
                                        <?= $terlambat ? 'Terlambat' : $r['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>