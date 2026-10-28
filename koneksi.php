<?php
// Konfigurasi koneksi database terpusat
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = ''; // Default XAMPP kosong
const DB_NAME = 'temukos_db';

// Aktifkan mode exception supaya error MySQL bisa ditangkap blok try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    // Catat pesan error asli ke log server, tampilkan pesan umum ke user
    error_log('[Koneksi Database Gagal] ' . $ex->getCode() . ': ' . $ex->getMessage());
    http_response_code(500);
    exit('Maaf, sistem sedang mengalami gangguan koneksi basis data.');
}