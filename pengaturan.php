<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data admin
$admin = $pdo->prepare("SELECT * FROM admin WHERE id_admin = ?");
$admin->execute([$_SESSION['admin_id']]);
$data_admin = $admin->fetch(PDO::FETCH_ASSOC);

// Update profil
if (isset($_POST['update_profil'])) {
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    
    $stmt = $pdo->prepare("UPDATE admin SET nama_lengkap = ?, email = ? WHERE id_admin = ?");
    if ($stmt->execute([$nama_lengkap, $email, $_SESSION['admin_id']])) {
        $_SESSION['admin_nama'] = $nama_lengkap;
        $success = "Profil berhasil diupdate";
        
        // Refresh data admin
        $admin->execute([$_SESSION['admin_id']]);
        $data_admin = $admin->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Gagal mengupdate profil";
    }
}

// Update password
if (isset($_POST['update_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    if (!password_verify($password_lama, $data_admin['password'])) {
        $error_password = "Password lama salah";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error_password = "Password baru dan konfirmasi tidak cocok";
    } else {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
        if ($stmt->execute([$password_hash, $_SESSION['admin_id']])) {
            $success_password = "Password berhasil diubah";
        } else {
            $error_password = "Gagal mengubah password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Perpustakaan Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="icon.png">
    <link rel = "stylesheet" href = "css/css.css">
</head>
<body class="bg-gray-900 text-gray-200">
    <div class="flex">
        <div class="w-64 bg-gray-800 text-gray-200 min-h-screen p-4">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-white">Pengaturan Sistem</h1>
            </div>
            
            <!-- Profil Admin -->
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4 text-white">Profil Admin</h2>
                
                <?php if (isset($success)): ?>
                <div class="bg-green-900 border border-green-700 text-green-300 px-4 py-3 rounded mb-4">
                    <?= $success ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="bg-red-900 border border-red-700 text-red-300 px-4 py-3 rounded mb-4">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">Username</label>
                        <input type="text" value="<?= $data_admin['username'] ?>" disabled 
                            class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-300">
                        <p class="text-xs text-gray-400 mt-1">Username tidak dapat diubah</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" required value="<?= htmlspecialchars($data_admin['nama_lengkap']) ?>"
                            class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">Email *</label>
                        <input type="email" name="email" required value="<?= $data_admin['email'] ?>"
                            class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" name="update_profil" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Update Profil
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Ubah Password -->
            <div class="bg-gray-800 rounded-xl shadow p-6 mb-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4 text-white">Ubah Password</h2>
                
                <?php if (isset($success_password)): ?>
                <div class="bg-green-900 border border-green-700 text-green-300 px-4 py-3 rounded mb-4">
                    <?= $success_password ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_password)): ?>
                <div class="bg-red-900 border border-red-700 text-red-300 px-4 py-3 rounded mb-4">
                    <?= $error_password ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">Password Lama *</label>
                        <input type="password" name="password_lama" required 
                            class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">Password Baru *</label>
                        <input type="password" name="password_baru" required 
                            class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-2">Konfirmasi Password *</label>
                        <input type="password" name="konfirmasi_password" required 
                            class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" name="update_password" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-key mr-2"></i>Ubah Password
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Informasi Sistem -->
            <div class="bg-gray-800 rounded-xl shadow p-6 border border-gray-700">
                <h2 class="text-xl font-bold mb-4 text-white">Informasi Sistem</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-bold mb-2 text-gray-200">Statistik Database</h3>
                        <ul class="space-y-2 text-gray-300">
                            <?php
                            $total_buku = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
                            $total_anggota = $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn();
                            $total_peminjaman = $pdo->query("SELECT COUNT(*) FROM peminjaman")->fetchColumn();
                            $total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
                            ?>
                            <li class="flex justify-between">
                                <span>Total Buku:</span>
                                <span class="font-bold"><?= $total_buku ?></span>
                            </li>
                            <li class="flex justify-between">
                                <span>Total Anggota:</span>
                                <span class="font-bold"><?= $total_anggota ?></span>
                            </li>
                            <li class="flex justify-between">
                                <span>Total Peminjaman:</span>
                                <span class="font-bold"><?= $total_peminjaman ?></span>
                            </li>
                            <li class="flex justify-between">
                                <span>Total Kategori:</span>
                                <span class="font-bold"><?= $total_kategori ?></span>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-bold mb-2 text-gray-200">Informasi Server</h3>
                        <ul class="space-y-2 text-gray-300">
                            <li class="flex justify-between">
                                <span>PHP Version:</span>
                                <span class="font-bold"><?= phpversion() ?></span>
                            </li>
                            <li class="flex justify-between">
                                <span>Server Software:</span>
                                <span class="font-bold"><?= $_SERVER['SERVER_SOFTWARE'] ?></span>
                            </li>
                            <li class="flex justify-between">
                                <span>Database:</span>
                                <span class="font-bold">MariaDB</span>
                            </li>
                            <li class="flex justify-between">
                                <span>Waktu Server:</span>
                                <span class="font-bold"><?= date('d/m/Y H:i:s') ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
