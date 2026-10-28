<?php
// Halaman detail kos (READ): menampilkan satu data kos berdasarkan parameter ?id= di URL
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';
require_once __DIR__ . '/tampilan.php';

wajibLogin();
$pengguna = penggunaAktif();

// Validasi parameter id dari URL: wajib bilangan bulat >= 1
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    tampilkanGalat(400, 'Id kos tidak valid', 'Alamat yang kamu buka tidak memuat id kos yang benar. Pilih kos dari daftar.');
}

try {
    // Prepared statement dengan parameter integer ('i') dan JOIN ke tabel pengguna
    $sql = 'SELECT k.*, u.nama AS pencatat, u.username AS username_pencatat
            FROM kos AS k
            JOIN pengguna AS u ON u.id = k.pengguna_id
            WHERE k.id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $kos = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('[Read Detail Gagal] ' . $ex->getMessage());
    tampilkanGalat(500, 'Detail belum bisa dimuat', 'Terjadi gangguan saat mengambil data. Coba lagi beberapa saat lagi.');
}

// Jika id valid tapi baris datanya tidak ada di database, kembalikan HTTP 404
if ($kos === null) {
    tampilkanGalat(404, 'Kos tidak ditemukan', 'Tidak ada kos dengan id tersebut. Mungkin alamatnya salah.');
}

$kodeKos = 'TK-' . str_pad((string) (int) $kos['id'], 3, '0', STR_PAD_LEFT);
$kelasKetersediaan = $kos['ketersediaan'] === 'Tersedia' ? 'status-tersedia' : 'status-penuh';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($kos['nama_kos']) ?> | TemuKos</title>
    <link rel="icon" href="assets/logo-temukos.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php topbarUtama($pengguna, 'katalog'); ?>

    <main class="container detail-main">
        <p class="back-row">
            <a href="daftar.php#katalog" class="btn btn-secondary">&larr; Kembali ke Daftar Kos</a>
        </p>

        <!-- Semua kolom kos yang ditampilkan ke HTML di-escape memakai e() -->
        <div class="detail-layout">
            <aside class="media-showcase">
                <div class="media-frame">
                    <?php if (!empty($kos['foto_kos']) && is_file(__DIR__ . '/' . $kos['foto_kos'])): ?>
                        <img src="<?= e($kos['foto_kos']) ?>" alt="Foto <?= e($kos['nama_kos']) ?>">
                    <?php else: ?>
                        <div class="card-visual-fallback"><span class="fallback-cat">Foto belum tersedia</span></div>
                    <?php endif; ?>
                    <span class="pill-top-left"><?= e($kos['tipe']) ?></span>
                    <span class="pill-top-right <?= $kelasKetersediaan ?>"><?= e($kos['ketersediaan']) ?></span>
                </div>

                <div class="price-highlight-box">
                    <div class="price-label">Harga sewa per bulan</div>
                    <div class="price-amount price-big"><?= e(formatRupiah((int) $kos['harga'])) ?></div>
                </div>
            </aside>

            <article class="panel-card">
                <p class="kode-kos">Kode kos <?= e($kodeKos) ?></p>
                <h1 class="section-title detail-title"><?= e($kos['nama_kos']) ?></h1>
                <p class="detail-meta">
                    Dicatat oleh <strong><?= e($kos['pencatat']) ?></strong> (@<?= e($kos['username_pencatat']) ?>) pada <?= e($kos['dibuat_pada']) ?>
                </p>

                <div class="dossier-grid">
                    <div class="dossier-item">
                        <div class="dossier-label">Tipe</div>
                        <div class="dossier-val"><?= e($kos['tipe']) ?></div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Ketersediaan</div>
                        <div class="dossier-val"><?= e($kos['ketersediaan']) ?></div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Fasilitas</div>
                        <div class="dossier-val"><?= e($kos['fasilitas']) ?></div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Kamar mandi</div>
                        <div class="dossier-val"><?= e($kos['kamar_mandi']) ?></div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Jarak ke kampus</div>
                        <div class="dossier-val"><?= e(formatJarak((int) $kos['jarak_m'])) ?></div>
                    </div>
                    <div class="dossier-item">
                        <div class="dossier-label">Kontak pengelola</div>
                        <div class="dossier-val"><?= e($kos['kontak']) ?></div>
                    </div>
                    <div class="dossier-item full-width">
                        <div class="dossier-label">Alamat</div>
                        <div class="dossier-val"><?= e($kos['alamat']) ?></div>
                    </div>
                </div>

                <div>
                    <div class="dossier-label">Keterangan dari pengelola</div>
                    <div class="desc-box"><?= ($kos['deskripsi'] ?? '') !== '' ? e($kos['deskripsi']) : 'Belum ada keterangan tambahan.' ?></div>
                </div>

                <p class="hint-box">
                    Sebelum membayar, tanyakan harga akhir, uang muka, dan aturan kos langsung ke pengelola.
                </p>
            </article>
        </div>
    </main>

    <?php footerSitus($pengguna); ?>
    <?php skripNavbar(); ?>
</body>
</html>