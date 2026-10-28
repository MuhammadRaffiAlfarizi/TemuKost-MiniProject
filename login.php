<?php
// Halaman login pengguna
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

if (penggunaAktif() !== null) {
    header('Location: daftar.php');
    exit;
}

// Hash dummy untuk menyamakan waktu proses password_verify saat username tidak ditemukan
const HASH_PALSU = '$2y$10$DPsCYCjaAT0IrGbfRA9Bhuy/7YsYSQbqTElwwYnQcAhEdnTyrFRVm';

$galat    = null;
$info     = isset($_GET['keluar']) ? 'Kamu sudah keluar dari akun.' : ambilFlash();
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = ambilPost('username');
    $sandi    = is_string($_POST['sandi'] ?? null) ? $_POST['sandi'] : '';

    if ($username === '' || $sandi === '') {
        $galat = 'Username dan kata sandi wajib diisi.';
    } else {
        try {
            // Ambil data akun berdasarkan username menggunakan prepared statement
            $stmt = $db->prepare('SELECT id, nama, peran, hash_sandi FROM pengguna WHERE username = ?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $pengguna = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Verifikasi kata sandi terhadap hash di database
            $cocok = password_verify($sandi, $pengguna['hash_sandi'] ?? HASH_PALSU);

            if ($pengguna !== null && $cocok) {
                // Perbarui session ID setelah login berhasil untuk mencegah session fixation
                session_regenerate_id(true);
                $_SESSION['id']       = (int) $pengguna['id'];
                $_SESSION['username'] = $username;
                $_SESSION['nama']     = $pengguna['nama'];
                $_SESSION['peran']    = $pengguna['peran'];

                setFlash('Selamat datang, ' . $pengguna['nama'] . '.');
                header('Location: daftar.php');
                exit;
            }
            // Gunakan pesan gagal yang sama baik saat username tidak ada maupun saat sandi salah
            $galat = 'Username atau kata sandi salah.';
        } catch (mysqli_sql_exception $ex) {
            error_log('[Login Gagal] ' . $ex->getMessage());
            $galat = 'Terjadi gangguan sistem saat memproses login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | TemuKos</title>
    <link rel="icon" href="assets/logo-temukos.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <img class="brand-logo" src="assets/logo-temukos.svg" alt="" width="38" height="38">
                <span>TemuKos</span>
            </div>
        </div>
    </header>

    <main class="auth-wrapper">
        <div class="panel">
            <h1 class="page-title">Masuk ke TemuKos</h1>
            <p class="page-desc">Gunakan akun pengelola (admin) untuk mencatat kos, atau akun pencari kos (anggota) untuk melihat daftar.</p>

            <?php if ($info !== null): ?>
                <div class="alert alert-success" role="status"><?= e($info) ?></div>
            <?php endif; ?>

            <?php if ($galat !== null): ?>
                <div class="alert alert-danger" role="alert"><?= e($galat) ?></div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" autofocus>
                </div>

                <div class="form-group">
                    <label for="sandi">Kata Sandi</label>
                    <input type="password" id="sandi" name="sandi" autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-block">Masuk</button>
            </form>

            <p class="auth-foot">
                Belum punya akun? <a href="registrasi.php">Buat akun pencari kos</a>
            </p>

            <div class="credential-box">
                <strong>Akun uji (kata sandi: <code>password</code>)</strong><br>
                Pengelola (admin): <code>admin</code><br>
                Pencari kos (anggota): <code>raffi</code>
            </div>
        </div>
    </main>
</body>
</html>