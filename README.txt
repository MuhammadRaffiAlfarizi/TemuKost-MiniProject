TEMUKOS - Katalog Kos Sekitar Kampus
Mini Project Pemrograman Web I (IF21305)
Nama : Muhammad Raffi Alfarizi
NIM  : H1D025032
Prodi: Informatika, Universitas Jenderal Soedirman

1. DESKRIPSI
TemuKos adalah aplikasi web katalog kos sekitar kampus. Pengelola kos (admin) mencatat data
kos, sedangkan pencari kos (anggota) melihat daftar dan detail kos. Dibuat dengan PHP
procedural dan MySQL (mysqli prepared statement).

2. KEBUTUHAN
- XAMPP (Apache + MySQL, PHP 8.x)
- Browser modern (Chrome, Edge, atau Firefox)

3. CARA MENJALANKAN
1) Jalankan Apache dan MySQL dari XAMPP Control Panel.
2) Extract zip, lalu salin folder H1D025032_MuhammadRaffiAlfarizi_MiniProject ke
   C:\xampp\htdocs\
   Pastikan file login.php langsung berada di dalam folder tersebut (bukan folder di dalam folder).
3) Buka http://localhost/phpmyadmin, pilih tab "Import", pilih file app.sql, klik "Import".
   File ini membuat database baru bernama temukos_db. Database lain tidak terpengaruh.
4) Buka http://localhost/H1D025032_MuhammadRaffiAlfarizi_MiniProject/
5) Bila MySQL memakai kata sandi untuk root, ubah DB_PASS di koneksi.php.

4. AKUN UJI (kata sandi semuanya: password)
- admin   : Pengelola TemuKos, peran admin (boleh mencatat kos)
- raffi   : peran anggota (pencari kos)
- hanifah : peran anggota (pencari kos)
Akun yang dibuat lewat halaman registrasi selalu berperan anggota.

5. FITUR
- Registrasi, login, dan logout (kata sandi di-hash, ID sesi diperbarui saat login).
- Create (khusus admin): catat kos baru lewat tambah.php, dengan foto opsional.
- Read: daftar kos dengan JOIN ke tabel pengguna (daftar.php) dan detail kos berdasarkan id (detail.php).
- Filter daftar berdasarkan tipe kos (Putra, Putri, Campur).
- Validasi sisi server: wajib isi, panjang, rentang angka, daftar putih ENUM, dan aturan lintas kolom.
- Keamanan: prepared statement, e() pada semua keluaran, pola Post/Redirect/Get, pembatasan peran (403).

6. VIDEO HERO (OPSIONAL)
Letakkan file video bernama hero.mp4 di folder utama proyek, sejajar dengan daftar.php.
Bila file tidak ada, bagian hero memakai gambar kos sebagai latar. Tidak ada kode yang perlu diubah.

7. STRUKTUR FILE
app.sql           skema database dan data awal (3 akun, 5 kos)
koneksi.php       koneksi database
sesi.php          sesi, fungsi e(), pesan flash, penjaga login dan peran, halaman galat
validasi.php      aturan validasi dan proses unggah foto
tampilan.php      navbar, footer, dan skrip menu bersama
index.php         pengarah awal (login atau daftar)
login.php, registrasi.php, logout.php
daftar.php        Read: daftar kos
detail.php        Read: detail kos
tambah.php        Create: catat kos (admin)
style.css         seluruh gaya tampilan
assets/           logo TemuKos dan gambar kos contoh
uploads/          tempat foto yang diunggah admin (dibuat otomatis bila hilang)
PENGUJIAN.docx    laporan pengujian 13 skenario beserta screenshot
CATATAN.txt       pemetaan tema, aturan validasi, dan catatan keamanan

8. CATATAN TAMBAHAN
- Semua data kos pada app.sql adalah data contoh fiktif.
- Fitur ubah, hapus, pencarian teks, dan paginasi tidak termasuk cakupan mini project ini.
