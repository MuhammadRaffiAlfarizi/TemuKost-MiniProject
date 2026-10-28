<?php
// Pengaturan session, fungsi escape output e(), flash message, dan penjaga akses halaman
date_default_timezone_set('Asia/Jakarta');

// Pengamanan cookie session sebelum session_start()
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true, // Cegah akses cookie session dari JavaScript
    'samesite' => 'Lax',
]);
session_start();

// Sanitasi output HTML untuk mencegah XSS.
// Semua data dari user atau database yang dicetak ke halaman wajib lewat fungsi ini.
function e(?string $teks): string
{
    return htmlspecialchars($teks ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Simpan pesan notifikasi sementara di session (dipakai setelah redirect PRG)
function setFlash(string $pesan): void
{
    $_SESSION['flash'] = $pesan;
}

// Ambil pesan flash lalu langsung hapus supaya tidak muncul lagi saat di-refresh
function ambilFlash(): ?string
{
    $pesan = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $pesan;
}

// Ambil data user yang sedang login dari session
function penggunaAktif(): ?array
{
    if (!isset($_SESSION['id'])) {
        return null;
    }
    return [
        'id'       => (int) $_SESSION['id'],
        'username' => $_SESSION['username'],
        'nama'     => $_SESSION['nama'],
        'peran'    => $_SESSION['peran'],
    ];
}

// Tampilkan halaman error dengan HTTP status code (400, 403, 404, 500) lalu hentikan script
function tampilkanGalat(int $kode, string $judul, string $pesan): never
{
    http_response_code($kode);
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($judul) ?> | TemuKos</title>
    <link rel="icon" href="assets/logo-temukos.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="auth-wrapper">
        <div class="panel">
            <h1 class="page-title"><?= e($judul) ?></h1>
            <p class="page-desc"><?= e($pesan) ?></p>
            <a href="daftar.php" class="btn">Kembali ke Daftar Kos</a>
        </div>
    </main>
</body>
</html>
<?php
    exit;
}

// Cek apakah user sudah login; kalau belum, lempar ke login.php
function wajibLogin(): void
{
    if (penggunaAktif() === null) {
        setFlash('Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.');
        header('Location: login.php');
        exit;
    }
}

// Cek peran user (misal khusus 'admin').
// Jika anggota biasa memaksa buka URL tambah.php, tolak dengan HTTP 403.
function wajibPeran(string $peranWajib): void
{
    wajibLogin();
    if (($_SESSION['peran'] ?? '') !== $peranWajib) {
        tampilkanGalat(
            403,
            'Akses ditolak',
            'Halaman ini hanya untuk pengelola kos (admin). Akunmu terdaftar sebagai pencari kos, jadi kamu bisa melihat daftar dan detail kos saja.'
        );
    }
}