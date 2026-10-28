-- =========================================================================
-- app.sql
-- Skema database dan data awal aplikasi TemuKos
-- Cara pakai: buka phpMyAdmin -> tab Import -> pilih file app.sql
-- =========================================================================

CREATE DATABASE IF NOT EXISTS temukos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE temukos_db;

DROP TABLE IF EXISTS kos;
DROP TABLE IF EXISTS pengguna;

-- Tabel akun pengguna
-- peran 'admin' bisa menambah data kos, 'anggota' hanya bisa melihat daftar & detail
CREATE TABLE pengguna (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,            -- UNIQUE: memicu error 1062 kalau kembar saat registrasi
  nama VARCHAR(100) NOT NULL,
  peran ENUM('admin','anggota') NOT NULL DEFAULT 'anggota',
  hash_sandi VARCHAR(255) NOT NULL,                -- menyimpan hasil password_hash(), bukan password asli
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabel data kos
CREATE TABLE kos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pengguna_id INT UNSIGNED NOT NULL,               -- ID admin penginput (diambil dari $_SESSION['id'])
  nama_kos VARCHAR(150) NOT NULL,
  tipe ENUM('Putra','Putri','Campur') NOT NULL,
  fasilitas ENUM('Kipas angin','AC') NOT NULL,
  kamar_mandi ENUM('Dalam','Luar') NOT NULL,
  harga INT UNSIGNED NOT NULL,                     -- harga per bulan (200.000 - 5.000.000)
  jarak_m INT UNSIGNED NOT NULL,                   -- jarak ke kampus dalam meter (0 - 20.000)
  alamat VARCHAR(150) NOT NULL,
  ketersediaan ENUM('Tersedia','Penuh') NOT NULL DEFAULT 'Tersedia',
  deskripsi VARCHAR(2000) DEFAULT NULL,            -- opsional, tapi wajib diisi kalau statusnya Penuh
  kontak VARCHAR(60) NOT NULL,
  foto_kos VARCHAR(255) DEFAULT NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pengguna_id) REFERENCES pengguna (id) -- relasi ke tabel pengguna untuk JOIN nama pencatat
);

-- Data awal akun uji (password semua akun: password)
INSERT INTO pengguna (username, nama, peran, hash_sandi) VALUES
('admin',   'Pengelola TemuKos',       'admin',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('raffi',   'Muhammad Raffi Alfarizi', 'anggota', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('hanifah', 'Hanifah Silmi Fajri',     'anggota', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Data awal 5 kos contoh
INSERT INTO kos (pengguna_id, nama_kos, tipe, fasilitas, kamar_mandi, harga, jarak_m, alamat, ketersediaan, deskripsi, kontak, foto_kos, dibuat_pada) VALUES
(1, 'Kos Melati Putri', 'Putri', 'AC', 'Dalam', 850000, 300,
 'Jl. Contoh Raya No. 12, Grendeng, Purwokerto Utara', 'Tersedia',
 'Kamar 3x4 meter dengan kasur dan lemari. Listrik token dibayar terpisah.',
 '0812-0000-0001', 'assets/kos-contoh.jpg', '2026-10-05 09:15:00'),
(1, 'Kos Dahlia Putra', 'Putra', 'Kipas angin', 'Luar', 450000, 600,
 'Gang Contoh 3 No. 7, Karangwangkal, Purwokerto Utara', 'Tersedia',
 'Kamar mandi luar dipakai bersama empat kamar. Parkir motor di halaman.',
 '0812-0000-0002', 'assets/kos-contoh.jpg', '2026-10-06 13:40:00'),
(1, 'Kos Anggrek Campur', 'Campur', 'AC', 'Dalam', 1200000, 1500,
 'Jl. Contoh Raya No. 45, Berkoh, Purwokerto Selatan', 'Penuh',
 'Semua kamar terisi sampai akhir semester. Perkiraan ada kamar kosong awal Januari.',
 '0812-0000-0003', 'assets/kos-contoh.jpg', '2026-10-08 15:05:00'),
(1, 'Kos Kenanga Putri', 'Putri', 'Kipas angin', 'Luar', 350000, 900,
 'Gang Contoh 8 No. 2, Karangwangkal, Purwokerto Utara', 'Tersedia',
 NULL,
 '0812-0000-0004', 'assets/kos-contoh.jpg', '2026-10-12 11:20:00'),
(1, 'Kos Cempaka Putra', 'Putra', 'AC', 'Dalam', 950000, 450,
 'Jl. Contoh Raya No. 80, Grendeng, Purwokerto Utara', 'Tersedia',
 'Kamar sudah termasuk akses wifi. Tidak ada jam malam.',
 '0812-0000-0005', 'assets/kos-contoh.jpg', '2026-10-14 16:30:00');