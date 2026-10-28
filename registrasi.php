<?php
// Halaman registrasi akun baru (otomatis mendapat peran 'anggota')
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

if (penggunaAktif() !== null) {
    header('Location: daftar.php');
    exit;
}

$errors = [];
$input  = ['username' => '', 'nama' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['username'] = ambilPost('username');
    $input['nama']     = ambilPost('nama');
    $sandi             = is_string($_POST['sandi'] ?? null) ? $_POST['sandi'] : '';

    // Validasi panjang & format karakter username, nama, serta kata sandi
    if ($err = validasiPanjang('Username', $input['username'], 3, 30)) {
        $errors['username'] = $err;
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $input['username'])) {
        $errors['username'] = 'Username hanya boleh berisi huruf, angka, dan garis bawah (_).';
    }

    if ($err = validasiPanjang('Nama Lengkap', $input['nama'], 3, 100)) {
        $errors['nama'] = $err;
    }

    if (mb_strlen($sandi) < 6) {
        $errors['sandi'] = 'Kata sandi wajib diisi minimal 6 karakter.';
    }

    // Jika semua input valid, hash password lalu simpan ke tabel pengguna
    if (empty($errors)) {
        $hash = password_hash($sandi, PASSWORD_DEFAULT);
        try {
            // Prepared statement untuk menyimpan akun baru dengan peran default 'anggota'
            $stmt = $db->prepare(
                'INSERT INTO pengguna (username, nama, peran, hash_sandi) VALUES (?, ?, "anggota", ?)'
            );
            $stmt->bind_param('sss', $input['username'], $input['nama'], $hash);
            $stmt->execute();
            $stmt->close();

            // Pola PRG: kirim pesan sukses lewat session lalu redirect ke halaman login
            setFlash('Akun berhasil dibuat. Silakan masuk.');
            header('Location: login.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            // Tangkap error MySQL 1062 jika username sudah terdaftar (pelanggaran UNIQUE)
            if ($ex->getCode() === 1062) {
                $errors['username'] = 'Username tersebut sudah dipakai. Silakan gunakan username lain.';
            } else {
                error_log('[Registrasi Gagal] ' . $ex->getMessage());
                $errors['umum'] = 'Terjadi kesalahan sistem saat menyimpan akun.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun | TemuKos</title>
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
            <h1 class="page-title">Buat akun pencari kos</h1>
            <p class="page-desc">Dengan akun ini kamu bisa melihat daftar kos, harga, fasilitas, dan kontak pengelola.</p>

            <?php if (isset($errors['umum'])): ?>
                <div class="alert alert-danger" role="alert"><?= e($errors['umum']) ?></div>
            <?php endif; ?>

            <form method="post" action="registrasi.php">
                <!-- Atribut value diisi kembali (sticky form) dan di-escape dengan e() -->
                <div class="form-group">
                    <label for="username">Username <span class="label-hint">(3-30 karakter, huruf, angka, garis bawah)</span></label>
                    <input type="text" id="username" name="username" value="<?= e($input['username']) ?>" autocomplete="username" class="<?= isset($errors['username']) ? 'input-error' : '' ?>">
                    <?php if (isset($errors['username'])): ?>
                        <span class="field-error"><?= e($errors['username']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="<?= e($input['nama']) ?>" autocomplete="name" class="<?= isset($errors['nama']) ? 'input-error' : '' ?>">
                    <?php if (isset($errors['nama'])): ?>
                        <span class="field-error"><?= e($errors['nama']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="sandi">Kata Sandi <span class="label-hint">(minimal 6 karakter)</span></label>
                    <input type="password" id="sandi" name="sandi" autocomplete="new-password" class="<?= isset($errors['sandi']) ? 'input-error' : '' ?>">
                    <?php if (isset($errors['sandi'])): ?>
                        <span class="field-error"><?= e($errors['sandi']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-block">Buat akun</button>
            </form>

            <p class="auth-foot">
                Sudah punya akun? <a href="login.php">Masuk</a>
            </p>
        </div>
    </main>
</body>
</html>