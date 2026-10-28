<?php
// index.php - Pintu masuk: pengguna yang sudah login ke daftar, tamu ke halaman masuk.
require_once __DIR__ . '/sesi.php';
header('Location: ' . (penggunaAktif() !== null ? 'daftar.php' : 'login.php'));
exit;
