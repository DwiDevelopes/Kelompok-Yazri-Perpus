<?php
session_start();
require_once 'config.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$gambar = null;
if (!empty($_FILES['gambar']['name'])) {
    $gambar = uploadGambar($_FILES['gambar']);
}

// Ambil kategori untuk dropdown
$kategori = $pdo->query("SELECT * FROM kategori")->fetchAll(PDO::FETCH_ASSOC);

// Proses tambah buku
if (isset($_POST['tambah_buku'])) {
    $judul = $_POST['judul'];
    $pengarang = $_POST['pengarang'];
    $id_kategori = $_POST['id_kategori'];
    $isbn = $_POST['isbn'];
    $tahun_terbit = $_POST['tahun_terbit'];
    $penerbit = $_POST['penerbit'];
    $deskripsi = $_POST['deskripsi'];
    $stok = $_POST['stok'];
    
    $stmt = $pdo->prepare("INSERT INTO buku (judul, pengarang, id_kategori, isbn, tahun_terbit, penerbit, deskripsi, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$judul, $pengarang, $id_kategori, $isbn, $tahun_terbit, $penerbit, $deskripsi, $stok, $gambar]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Perpustakaan Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            transition: background-color 0.3s, color 0.3s;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 dark">
    <div class="flex">
        <div class="w-64 bg-gray-800 min-h-screen p-4 border-r border-gray-700">
            <?php include 'sidebar.php'; ?>
        </div>
    <link rel="icon" type="image/x-icon" href="icon.png">
        
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-white">Tambah Buku Baru</h1>
                <a href="koleksi_buku.php" class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center space-x-2 transition duration-200">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-900 border border-red-700 text-red-200 px-4 py-3 rounded mb-4">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="judul">
                                Judul Buku *
                            </label>
                            <input type="text" id="judul" name="judul" required 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="pengarang">
                                Pengarang *
                            </label>
                            <input type="text" id="pengarang" name="pengarang" required 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="id_kategori">
                                Kategori *
                            </label>
                            <select id="id_kategori" name="id_kategori" required 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white">
                                <option value="" class="bg-gray-700">Pilih Kategori</option>
                                <?php foreach($kategori as $kat): ?>
                                <option value="<?= $kat['id_kategori'] ?>" class="bg-gray-700"><?= $kat['nama_kategori'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="isbn">
                                ISBN
                            </label>
                            <input type="text" id="isbn" name="isbn" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="tahun_terbit">
                                Tahun Terbit
                            </label>
                            <input type="number" id="tahun_terbit" name="tahun_terbit" min="1900" max="<?= date('Y') ?>" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="penerbit">
                                Penerbit
                            </label>
                            <input type="text" id="penerbit" name="penerbit" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="deskripsi">
                                Deskripsi
                            </label>
                            <textarea id="deskripsi" name="deskripsi" rows="4" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="gambar">
                                Cover Buku
                            </label>
                            <input type="file" id="gambar" name="gambar" accept="image/*"
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white file:bg-gray-600 file:border-0 file:text-white file:rounded file:px-3 file:py-2 file:mr-4">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 text-sm font-bold mb-2" for="stok">
                                Stok *
                            </label>
                            <input type="number" id="stok" name="stok" required min="1" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400">
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" name="tambah_buku" 
                            class="bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-600 flex items-center space-x-2 transition duration-200">
                            <i class="fas fa-save"></i>
                            <span>Simpan Buku</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>