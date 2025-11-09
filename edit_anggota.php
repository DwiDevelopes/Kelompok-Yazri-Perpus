<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id_anggota = $_GET['id'] ?? 0;

// Ambil data anggota yang akan diedit
$anggota = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = ?");
$anggota->execute([$id_anggota]);
$data_anggota = $anggota->fetch(PDO::FETCH_ASSOC);

if (!$data_anggota) {
    header('Location: anggota.php');
    exit;
}

// Proses update anggota
if (isset($_POST['update_anggota'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE anggota SET nama = ?, email = ?, telepon = ?, alamat = ?, status = ? WHERE id_anggota = ?");
    
    try {
        if ($stmt->execute([$nama, $email, $telepon, $alamat, $status, $id_anggota])) {
            header('Location: anggota.php?success=Data anggota berhasil diupdate');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Gagal mengupdate anggota. Email mungkin sudah digunakan oleh anggota lain.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Anggota - Perpustakaan Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="icon.png">
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
        
        /* Style untuk form inputs di dark mode */
        input, select, textarea {
            background-color: #374151 !important;
            color: #f9fafb !important;
        }
        
        input:focus, select:focus, textarea:focus {
            background-color: #4b5563 !important;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="flex">
        <div class="w-64 bg-gray-800 text-gray-100 min-h-screen p-4">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-white">Edit Data Anggota</h1>
                <a href="anggota.php" class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center space-x-2 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-900 border border-red-700 text-red-300 px-4 py-3 rounded mb-4">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="nama">
                                Nama Lengkap *
                            </label>
                            <input type="text" id="nama" name="nama" required 
                                value="<?= htmlspecialchars($data_anggota['nama']) ?>"
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white bg-gray-700">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="email">
                                Email *
                            </label>
                            <input type="email" id="email" name="email" required 
                                value="<?= htmlspecialchars($data_anggota['email']) ?>"
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white bg-gray-700">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="telepon">
                                Telepon
                            </label>
                            <input type="text" id="telepon" name="telepon" 
                                value="<?= htmlspecialchars($data_anggota['telepon']) ?>"
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white bg-gray-700">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="status">
                                Status *
                            </label>
                            <select id="status" name="status" required 
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white bg-gray-700">
                                <option value="Aktif" <?= $data_anggota['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="Nonaktif" <?= $data_anggota['status'] == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="alamat">
                                Alamat
                            </label>
                            <textarea id="alamat" name="alamat" rows="3"
                                class="w-full px-3 py-2 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white bg-gray-700"><?= htmlspecialchars($data_anggota['alamat']) ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <div class="bg-gray-700 p-4 rounded-lg border border-gray-600">
                                <h3 class="font-bold text-gray-300 mb-2">Informasi Tambahan:</h3>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-400">ID Anggota:</span>
                                        <span class="font-bold text-white"><?= $data_anggota['id_anggota'] ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">Tanggal Daftar:</span>
                                        <span class="font-bold text-white"><?= date('d/m/Y', strtotime($data_anggota['tanggal_daftar'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="anggota.php" class="bg-gray-700 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                            Batal
                        </a>
                        <button type="submit" name="update_anggota" 
                            class="bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-600 flex items-center space-x-2 transition-colors">
                            <i class="fas fa-save"></i>
                            <span>Update Data Anggota</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>