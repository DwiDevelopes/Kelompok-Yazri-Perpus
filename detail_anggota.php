<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id_anggota = $_GET['id'] ?? 0;

// Ambil data anggota
$anggota = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = ?");
$anggota->execute([$id_anggota]);
$data_anggota = $anggota->fetch(PDO::FETCH_ASSOC);

if (!$data_anggota) {
    header('Location: anggota.php');
    exit;
}

// Ambil riwayat peminjaman anggota
$riwayat_peminjaman = $pdo->prepare("
    SELECT p.*, b.judul, b.pengarang 
    FROM peminjaman p 
    JOIN buku b ON p.id_buku = b.id_buku 
    WHERE p.id_anggota = ? 
    ORDER BY p.tanggal_pinjam DESC
");
$riwayat_peminjaman->execute([$id_anggota]);
$riwayat = $riwayat_peminjaman->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$total_peminjaman = count($riwayat);
$sedang_dipinjam = 0;
$terlambat = 0;

foreach ($riwayat as $p) {
    if ($p['status'] == 'Dipinjam') $sedang_dipinjam++;
    if ($p['status'] == 'Terlambat') $terlambat++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Anggota - Perpustakaan Digital</title>
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
                <h1 class="text-2xl font-bold text-white">Detail Anggota</h1>
                <div class="flex space-x-2">
                    <a href="edit_anggota.php?id=<?= $data_anggota['id_anggota'] ?>" 
                       class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-600 flex items-center space-x-2 transition-colors">
                        <i class="fas fa-edit"></i>
                        <span>Edit</span>
                    </a>
                    <a href="anggota.php" class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center space-x-2 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
            
            <!-- Informasi Anggota -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Data Pribadi -->
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 lg:col-span-2 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4 text-white">Data Pribadi</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-1">ID Anggota</label>
                            <p class="text-gray-100 font-semibold"><?= $data_anggota['id_anggota'] ?></p>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-1">Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?= $data_anggota['status'] == 'Aktif' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' ?>">
                                <?= $data_anggota['status'] ?>
                            </span>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-400 text-sm font-bold mb-1">Nama Lengkap</label>
                            <p class="text-gray-100 font-semibold"><?= htmlspecialchars($data_anggota['nama']) ?></p>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-1">Email</label>
                            <p class="text-gray-100"><?= $data_anggota['email'] ?></p>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-1">Telepon</label>
                            <p class="text-gray-100"><?= $data_anggota['telepon'] ?: '-' ?></p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-400 text-sm font-bold mb-1">Alamat</label>
                            <p class="text-gray-100"><?= $data_anggota['alamat'] ? nl2br(htmlspecialchars($data_anggota['alamat'])) : '-' ?></p>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-1">Tanggal Daftar</label>
                            <p class="text-gray-100"><?= date('d F Y', strtotime($data_anggota['tanggal_daftar'])) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Statistik -->
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4 text-white">Statistik</h2>
                    <div class="space-y-4">
                        <div class="text-center p-4 bg-blue-900/30 rounded-lg border border-blue-700/50">
                            <div class="text-2xl font-bold text-blue-400"><?= $total_peminjaman ?></div>
                            <div class="text-sm text-gray-300">Total Peminjaman</div>
                        </div>
                        <div class="text-center p-4 bg-green-900/30 rounded-lg border border-green-700/50">
                            <div class="text-2xl font-bold text-green-400"><?= $sedang_dipinjam ?></div>
                            <div class="text-sm text-gray-300">Sedang Dipinjam</div>
                        </div>
                        <div class="text-center p-4 bg-red-900/30 rounded-lg border border-red-700/50">
                            <div class="text-2xl font-bold text-red-400"><?= $terlambat ?></div>
                            <div class="text-sm text-gray-300">Keterlambatan</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Riwayat Peminjaman -->
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4 text-white">Riwayat Peminjaman</h2>
                
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Buku</th>
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
                                    <div class="font-medium text-white"><?= htmlspecialchars($r['judul']) ?></div>
                                    <div class="text-sm text-gray-400"><?= $r['pengarang'] ?></div>
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