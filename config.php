<?php
// config.php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'perpustakaan_digital';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi helper upload gambar
function uploadGambar($file, $target_dir = "uploads/") {
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Validasi file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception("Hanya file JPEG, PNG, GIF, dan WebP yang diizinkan");
    }
    
    if ($file['size'] > $max_size) {
        throw new Exception("Ukuran file maksimal 2MB");
    }
    
    $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $file_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    
    throw new Exception("Gagal mengupload file");
}
?>