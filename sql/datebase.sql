-- Buat database
CREATE DATABASE perpustakaan_digital;
USE perpustakaan_digital;

-- Tabel anggota
CREATE TABLE anggota (
    id_anggota INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telepon VARCHAR(15),
    alamat TEXT,
    tanggal_daftar DATE,
    status ENUM('Aktif', 'Nonaktif') DEFAULT 'Aktif'
);

-- Tabel kategori buku
CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL
);

-- Tabel buku
CREATE TABLE buku (
    id_buku INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    pengarang VARCHAR(100) NOT NULL,
    id_kategori INT,
    isbn VARCHAR(20),
    tahun_terbit YEAR,
    penerbit VARCHAR(100),
    deskripsi TEXT,
    stok INT DEFAULT 1,
    gambar VARCHAR(255),
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)
);

-- Tabel peminjaman
CREATE TABLE peminjaman (
    id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
    id_buku INT,
    id_anggota INT,
    tanggal_pinjam DATE,
    tanggal_kembali DATE,
    status ENUM('Dipinjam', 'Dikembalikan', 'Terlambat') DEFAULT 'Dipinjam',
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku),
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota)
);

-- Tabel admin
CREATE TABLE admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE user (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

-- Insert data contoh
INSERT INTO kategori (nama_kategori) VALUES 
('Teknologi'), ('Sejarah'), ('Matematika'), ('Sastra'), ('Filsafat');

INSERT INTO buku (judul, pengarang, id_kategori, isbn, tahun_terbit, penerbit, stok) VALUES
('Pemrograman Web Modern', 'Budi Santoso', 1, '978-1234567890', 2022, 'Penerbit Informatika', 5),
('Sistem Basis Data', 'Ani Wijaya', 1, '978-1234567891', 2021, 'Penerbit Komputasi', 3),
('Sejarah Indonesia Modern', 'Rudi Hermawan', 2, '978-1234567892', 2020, 'Penerbit Sejarah', 4),
('Kalkulus Dasar', 'Dewi Sartika', 3, '978-1234567893', 2019, 'Penerbit Matematika', 2);

INSERT INTO anggota (nama, email, telepon, alamat, tanggal_daftar) VALUES
('Ahmad Fauzi', 'ahmad@example.com', '081234567890', 'Jl. Merdeka No. 123', '2023-01-15'),
('Siti Rahma', 'siti@example.com', '081234567891', 'Jl. Sudirman No. 45', '2023-02-20'),
('Rizki Pratama', 'rizki@example.com', '081234567892', 'Jl. Gatot Subroto No. 67', '2023-03-10');

INSERT INTO peminjaman (id_buku, id_anggota, tanggal_pinjam, tanggal_kembali, status) VALUES
(2, 1, '2023-10-01', NULL, 'Dipinjam'),
(1, 2, '2023-09-28', '2023-10-05', 'Dikembalikan'),
(3, 3, '2023-09-25', NULL, 'Terlambat');

INSERT INTO admin (username, password, nama_lengkap, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Perpus', 'admin@perpustakaan.com');

INSERT INTO user (username, password, nama_lengkap, email) VALUES
('user', 'siswa', 'User Perpus', 'user@perpustakaan.com');
-- Password: password

-- Tambah kolom gambar ke tabel buku
ALTER TABLE buku ADD COLUMN gambar VARCHAR(255) DEFAULT NULL;

-- Update data buku dengan gambar contoh
UPDATE buku SET gambar = 'pemrograman_web.jpg' WHERE id_buku = 1;
UPDATE buku SET gambar = 'basis_data.jpg' WHERE id_buku = 2;
UPDATE buku SET gambar = 'sejarah_indonesia.jpg' WHERE id_buku = 3;
UPDATE buku SET gambar = 'kalkulus.jpg' WHERE id_buku = 4;

-- Tabel untuk QR Code
CREATE TABLE qr_codes (
    id_qr INT AUTO_INCREMENT PRIMARY KEY,
    id_buku INT,
    kode_qr VARCHAR(100) UNIQUE NOT NULL,
    tanggal_buat DATE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
);

ALTER TABLE admin ADD COLUMN foto_profil VARCHAR(255) DEFAULT NULL;