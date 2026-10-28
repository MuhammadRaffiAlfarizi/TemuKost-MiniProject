<?php
// Halaman tambah data kos (CREATE): hanya bisa diakses oleh peran 'admin'
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';
require_once __DIR__ . '/tampilan.php';

// Tolak akses jika bukan admin (menampilkan halaman error HTTP 403)
wajibPeran('admin');
$pengguna = penggunaAktif();

$errors = [];
$input  = [
    'nama_kos'     => '',
    'tipe'         => '',
    'fasilitas'    => '',
    'kamar_mandi'  => '',
    'harga'        => '',
    'jarak_m'      => '',
    'alamat'       => '',
    'ketersediaan' => 'Tersedia',
    'deskripsi'    => '',
    'kontak'       => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($input) as $kunci) {
        $input[$kunci] = ambilPost($kunci);
    }

    // 1. Validasi umum: wajib isi, panjang teks, whitelist ENUM, dan rentang angka
    if ($err = validasiPanjang('Nama Kos', $input['nama_kos'], 3, 150)) {
        $errors['nama_kos'] = $err;
    }
    if ($err = validasiPilihan('Tipe Kos', $input['tipe'], DAFTAR_TIPE)) {
        $errors['tipe'] = $err;
    }
    if ($err = validasiPilihan('Fasilitas', $input['fasilitas'], DAFTAR_FASILITAS)) {
        $errors['fasilitas'] = $err;
    }
    if ($err = validasiPilihan('Kamar Mandi', $input['kamar_mandi'], DAFTAR_KAMAR_MANDI)) {
        $errors['kamar_mandi'] = $err;
    }
    if ($err = validasiPilihan('Ketersediaan', $input['ketersediaan'], DAFTAR_KETERSEDIAAN)) {
        $errors['ketersediaan'] = $err;
    }
    if ($err = validasiAngka('Harga sewa', $input['harga'], HARGA_MIN, HARGA_MAX)) {
        $errors['harga'] = $err;
    }
    if ($err = validasiAngka('Jarak ke kampus (meter)', $input['jarak_m'], 0, JARAK_MAKS_M)) {
        $errors['jarak_m'] = $err;
    }
    if ($err = validasiPanjang('Alamat', $input['alamat'], 10, 150)) {
        $errors['alamat'] = $err;
    }
    if ($err = validasiPanjang('Kontak', $input['kontak'], 5, 60)) {
        $errors['kontak'] = $err;
    }

    // 2. Validasi khusus tema (silang antar kolom):
    // - Fasilitas AC wajib harga minimal Rp 500.000
    // - Status Penuh wajib isi deskripsi minimal 10 karakter
    if (!isset($errors['harga']) && ($err = validasiHargaAc($input['fasilitas'], $input['harga']))) {
        $errors['harga'] = $err;
    }
    if ($err = validasiDeskripsi($input['ketersediaan'], $input['deskripsi'])) {
        $errors['deskripsi'] = $err;
    }

    // 3. Proses unggah foto (opsional) jika semua validasi teks/angka sudah lolos
    $jalurFoto = null;
    if (empty($errors)) {
        $jalurFoto = prosesUnggahFoto('foto_kos', $errors);
    }

    // 4. Simpan ke database menggunakan prepared statement
    if (empty($errors)) {
        $penggunaId = (int) $_SESSION['id']; // ID pencatat diambil dari session login, bukan dari form
        $harga      = (int) $input['harga'];
        $jarak      = (int) $input['jarak_m'];
        $deskripsi  = $input['deskripsi'] !== '' ? $input['deskripsi'] : null;

        try {
            $sql = 'INSERT INTO kos
                        (pengguna_id, nama_kos, tipe, fasilitas, kamar_mandi, harga, jarak_m,
                         alamat, ketersediaan, deskripsi, kontak, foto_kos)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $db->prepare($sql);
            $stmt->bind_param(
                'issssiisssss',
                $penggunaId,
                $input['nama_kos'],
                $input['tipe'],
                $input['fasilitas'],
                $input['kamar_mandi'],
                $harga,
                $jarak,
                $input['alamat'],
                $input['ketersediaan'],
                $deskripsi,
                $input['kontak'],
                $jalurFoto
            );
            $stmt->execute();
            $stmt->close();

            // Pola PRG: simpan pesan sukses di session lalu redirect ke daftar.php
            setFlash('Kos "' . $input['nama_kos'] . '" berhasil ditambahkan ke daftar.');
            header('Location: daftar.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            error_log('[Create Kos Gagal] ' . $ex->getMessage());
            $errors['umum'] = 'Maaf, terjadi kesalahan saat menyimpan data kos. Isianmu masih ada, coba simpan lagi.';
        }
    }
}

// Helper untuk menandai input yang salah dan mencetak pesan error di bawah kolomnya
function kelasInput(array $errors, string $kunci): string
{
    return isset($errors[$kunci]) ? 'input-error' : '';
}
function pesanGalat(array $errors, string $kunci): void
{
    if (isset($errors[$kunci])) {
        echo '<span class="field-error">' . e($errors[$kunci]) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catat Kos Baru | TemuKos</title>
    <link rel="icon" href="assets/logo-temukos.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php topbarUtama($pengguna, 'tambah'); ?>

    <main class="container-narrow">
        <div class="panel-card">
            <h1 class="section-title form-title">Catat kos baru</h1>
            <p class="form-intro">
                Harga sewa 200.000 sampai 5.000.000 per bulan. Kos <strong>AC</strong> minimal 500.000.
                Kos berstatus <strong>Penuh</strong> wajib diberi deskripsi minimal 10 karakter.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php if (isset($errors['umum'])): ?>
                        <?= e($errors['umum']) ?>
                    <?php else: ?>
                        Kos belum disimpan. Ada <strong><?= count($errors) ?> kolom</strong> yang perlu diperbaiki, ditandai merah di bawah.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Sticky form: nilai $input dikembalikan ke masing-masing field dan di-escape dengan e() -->
            <form method="post" action="tambah.php" enctype="multipart/form-data" novalidate>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="nama_kos">Nama Kos <span class="label-hint">(3-150 karakter)</span></label>
                        <input type="text" id="nama_kos" name="nama_kos" value="<?= e($input['nama_kos']) ?>" placeholder="Contoh: Kos Melati Putri" class="<?= kelasInput($errors, 'nama_kos') ?>">
                        <?php pesanGalat($errors, 'nama_kos'); ?>
                    </div>

                    <div class="form-group">
                        <label for="tipe">Tipe Kos</label>
                        <select id="tipe" name="tipe" class="<?= kelasInput($errors, 'tipe') ?>">
                            <option value="">Pilih tipe</option>
                            <?php foreach (DAFTAR_TIPE as $t): ?>
                                <option value="<?= e($t) ?>" <?= $input['tipe'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php pesanGalat($errors, 'tipe'); ?>
                    </div>

                    <div class="form-group">
                        <label for="ketersediaan">Ketersediaan</label>
                        <select id="ketersediaan" name="ketersediaan" class="<?= kelasInput($errors, 'ketersediaan') ?>">
                            <?php foreach (DAFTAR_KETERSEDIAAN as $k): ?>
                                <option value="<?= e($k) ?>" <?= $input['ketersediaan'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php pesanGalat($errors, 'ketersediaan'); ?>
                    </div>

                    <div class="form-group">
                        <label for="fasilitas">Fasilitas</label>
                        <select id="fasilitas" name="fasilitas" class="<?= kelasInput($errors, 'fasilitas') ?>">
                            <option value="">Pilih fasilitas</option>
                            <?php foreach (DAFTAR_FASILITAS as $f): ?>
                                <option value="<?= e($f) ?>" <?= $input['fasilitas'] === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php pesanGalat($errors, 'fasilitas'); ?>
                    </div>

                    <div class="form-group">
                        <label for="kamar_mandi">Kamar Mandi</label>
                        <select id="kamar_mandi" name="kamar_mandi" class="<?= kelasInput($errors, 'kamar_mandi') ?>">
                            <option value="">Pilih kamar mandi</option>
                            <?php foreach (DAFTAR_KAMAR_MANDI as $km): ?>
                                <option value="<?= e($km) ?>" <?= $input['kamar_mandi'] === $km ? 'selected' : '' ?>><?= e($km) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php pesanGalat($errors, 'kamar_mandi'); ?>
                    </div>

                    <div class="form-group">
                        <label for="harga">Harga Sewa per Bulan (Rp) <span class="label-hint">(200.000-5.000.000)</span></label>
                        <input type="number" id="harga" name="harga" min="200000" max="5000000" value="<?= e($input['harga']) ?>" placeholder="Contoh: 850000" class="<?= kelasInput($errors, 'harga') ?>">
                        <?php pesanGalat($errors, 'harga'); ?>
                    </div>

                    <div class="form-group">
                        <label for="jarak_m">Jarak ke Kampus (meter) <span class="label-hint">(0-20.000)</span></label>
                        <input type="number" id="jarak_m" name="jarak_m" min="0" max="20000" value="<?= e($input['jarak_m']) ?>" placeholder="Contoh: 300" class="<?= kelasInput($errors, 'jarak_m') ?>">
                        <?php pesanGalat($errors, 'jarak_m'); ?>
                    </div>

                    <div class="form-group full-width">
                        <label for="alamat">Alamat <span class="label-hint">(10-150 karakter)</span></label>
                        <input type="text" id="alamat" name="alamat" value="<?= e($input['alamat']) ?>" placeholder="Contoh: Jl. Contoh Raya No. 12, Grendeng, Purwokerto Utara" class="<?= kelasInput($errors, 'alamat') ?>">
                        <?php pesanGalat($errors, 'alamat'); ?>
                    </div>

                    <div class="form-group full-width">
                        <label for="kontak">Kontak Pengelola <span class="label-hint">(nomor telepon atau email)</span></label>
                        <input type="text" id="kontak" name="kontak" value="<?= e($input['kontak']) ?>" placeholder="Contoh: 0812-0000-0000" class="<?= kelasInput($errors, 'kontak') ?>">
                        <?php pesanGalat($errors, 'kontak'); ?>
                    </div>

                    <div class="form-group full-width">
                        <label for="foto_kos">Foto Kos <span class="label-hint">(opsional, JPG/PNG/WEBP, maksimal 2 MB)</span></label>
                        <div class="upload-zone">
                            <input type="file" id="foto_kos" name="foto_kos" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <?php pesanGalat($errors, 'foto_kos'); ?>
                    </div>

                    <div class="form-group full-width">
                        <label for="deskripsi">Deskripsi <span class="label-hint">(opsional, maksimal 2000 karakter; wajib bila status Penuh)</span></label>
                        <textarea id="deskripsi" name="deskripsi" rows="4" class="<?= kelasInput($errors, 'deskripsi') ?>"><?= e($input['deskripsi']) ?></textarea>
                        <?php pesanGalat($errors, 'deskripsi'); ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Simpan Kos</button>
                    <a href="daftar.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </main>

    <?php footerSitus($pengguna); ?>
    <?php skripNavbar(); ?>
</body>
</html>