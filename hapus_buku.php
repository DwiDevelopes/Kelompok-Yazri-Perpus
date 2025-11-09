<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id_buku = $_GET['id'];
    
    // Cek apakah buku sedang dipinjam
    $cek_peminjaman = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_buku = ? AND status IN ('Dipinjam', 'Terlambat')");
    $cek_peminjaman->execute([$id_buku]);
    $sedang_dipinjam = $cek_peminjaman->fetchColumn();
    
    if ($sedang_dipinjam > 0) {
        header('Location: koleksi_buku.php?error=Buku tidak dapat dihapus karena sedang dipinjam');
        exit;
    }
    
    // Hapus gambar jika ada
    $buku = $pdo->prepare("SELECT gambar FROM buku WHERE id_buku = ?");
    $buku->execute([$id_buku]);
    $data_buku = $buku->fetch(PDO::FETCH_ASSOC);
    
    if ($data_buku['gambar'] && file_exists($data_buku['gambar'])) {
        unlink($data_buku['gambar']);
    }
    
    // Hapus buku
    $stmt = $pdo->prepare("DELETE FROM buku WHERE id_buku = ?");
    if ($stmt->execute([$id_buku])) {
        header('Location: koleksi_buku.php?success=Buku berhasil dihapus');
    } else {
        header('Location: koleksi_buku.php?error=Gagal menghapus buku');
    }
    exit;
}

header('Location: koleksi_buku.php');
?>