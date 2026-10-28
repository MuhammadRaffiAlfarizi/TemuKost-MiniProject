<?php
// Halaman daftar kos (READ): menampilkan semua data kos diurutkan dari yang terbaru
require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';
require_once __DIR__ . '/tampilan.php';

wajibLogin();

$pengguna = penggunaAktif();
$pesan    = ambilFlash();

try {
    // Ambil seluruh data kos beserta nama pencatatnya lewat JOIN ke tabel pengguna
    $sql = 'SELECT k.id, k.nama_kos, k.tipe, k.fasilitas, k.kamar_mandi, k.harga, k.jarak_m,
                   k.alamat, k.ketersediaan, k.deskripsi, k.foto_kos, k.dibuat_pada,
                   u.nama AS pencatat
            FROM kos AS k
            JOIN pengguna AS u ON u.id = k.pengguna_id
            ORDER BY k.dibuat_pada DESC, k.id DESC';
    $daftar = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $ex) {
    error_log('[Read Daftar Gagal] ' . $ex->getMessage());
    tampilkanGalat(500, 'Daftar belum bisa dimuat', 'Terjadi gangguan saat mengambil data kos. Muat ulang halaman ini beberapa saat lagi.');
}

// Validasi parameter filter tipe dari URL terhadap whitelist DAFTAR_TIPE
$filterTipe = $_GET['tipe'] ?? 'Semua';
if (!is_string($filterTipe) || !in_array($filterTipe, array_merge(['Semua'], DAFTAR_TIPE), true)) {
    $filterTipe = 'Semua';
}
$sedangFilter = $filterTipe !== 'Semua';

// Hitung jumlah kos pada masing-masing tipe untuk ditampilkan di tombol filter
$jumlahPerTipe = array_fill_keys(DAFTAR_TIPE, 0);
foreach ($daftar as $baris) {
    $jumlahPerTipe[$baris['tipe']]++;
}

$adaVideo = is_file(__DIR__ . '/hero.mp4');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda dan Daftar Kos | TemuKos</title>
    <link rel="icon" href="assets/logo-temukos.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php topbarUtama($pengguna, $sedangFilter ? 'katalog' : 'beranda'); ?>

    <section class="hero-banner">
        <?php if ($adaVideo): ?>
            <video class="hero-video" autoplay loop muted playsinline preload="auto" poster="assets/kos-contoh.jpg">
                <source src="hero.mp4" type="video/mp4">
            </video>
        <?php endif; ?>
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <h1 class="hero-title">Temukan Kos yang Pas untuk Kuliahmu</h1>
            <p class="hero-subtitle">
                TemuKos mengumpulkan info kos sekitar kampus: harga per bulan, fasilitas, jarak, dan status kamar dalam satu daftar.
            </p>
            <?php if ($pengguna['peran'] === 'admin'): ?>
                <a href="tambah.php" class="btn-orange-pill">Catat Kos Baru</a>
            <?php else: ?>
                <a href="#katalog" class="btn-orange-pill">Lihat Daftar Kos</a>
            <?php endif; ?>
        </div>
    </section>

    <div class="main-sheet">
        <div class="container">
            <?php if ($pesan !== null): ?>
                <div class="alert alert-success" role="status"><?= e($pesan) ?></div>
            <?php endif; ?>

            <section id="solusi" class="info-section">
                <div class="section-head">
                    <h2 class="section-title">Info kos yang bisa langsung <span class="text-orange">dibandingkan</span></h2>
                    <p class="section-subtitle">
                        Setiap kos dicatat pengelola dengan data yang sama, jadi kamu tidak perlu bertanya satu per satu.
                    </p>
                    <div class="section-divider"></div>
                </div>

                <div class="info-grid">
                    <article class="info-utama">
                        <h3 class="info-judul">Harga dan fasilitas harus masuk akal</h3>
                        <p>
                            Harga sewa dicatat per bulan dan dibatasi antara <?= e(formatRupiah(HARGA_MIN)) ?> dan <?= e(formatRupiah(HARGA_MAX)) ?>.
                            Kos berfasilitas AC tidak bisa dicatat di bawah <?= e(formatRupiah(HARGA_MIN_AC)) ?>, sehingga angka yang kamu lihat
                            sesuai dengan fasilitasnya.
                        </p>
                    </article>
                    <article class="info-samping">
                        <h3 class="info-judul">Jarak ke kampus</h3>
                        <p>Dicatat dalam meter, dari 0 sampai <?= e(formatJarak(JARAK_MAKS_M)) ?>. Berguna untuk memilih kos yang cukup dekat.</p>
                    </article>
                    <article class="info-samping">
                        <h3 class="info-judul">Status kamar</h3>
                        <p>Tersedia atau Penuh. Kos berstatus Penuh wajib memuat keterangan, misalnya kapan kamar kosong lagi.</p>
                    </article>
                </div>
            </section>

            <section id="katalog" class="catalog-section">
                <div class="section-head">
                    <h2 class="section-title">Daftar kos <span class="text-orange">terbaru</span></h2>
                    <p class="section-subtitle">
                        Pilih tipe kos untuk menyaring daftar. Buka satu kos untuk melihat alamat, fasilitas, dan kontak pengelola.
                    </p>
                </div>

                <nav class="category-pills" aria-label="Filter tipe kos">
                    <a href="daftar.php#katalog" class="cat-pill <?= !$sedangFilter ? 'active' : '' ?>">Semua (<?= count($daftar) ?>)</a>
                    <?php foreach (DAFTAR_TIPE as $tipe): ?>
                        <a href="daftar.php?tipe=<?= urlencode($tipe) ?>#katalog" class="cat-pill <?= $filterTipe === $tipe ? 'active' : '' ?>">
                            <?= e($tipe) ?> (<?= (int) $jumlahPerTipe[$tipe] ?>)
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="catalog-grid">
                    <?php
                    $jumlahTampil = 0;
                    foreach ($daftar as $baris):
                        if ($sedangFilter && $baris['tipe'] !== $filterTipe) {
                            continue;
                        }
                        $jumlahTampil++;
                        $inisial = mb_strtoupper(mb_substr($baris['pencatat'], 0, 1));
                        $kelasKetersediaan = $baris['ketersediaan'] === 'Tersedia' ? 'status-tersedia' : 'status-penuh';
                    ?>
                        <!-- Seluruh data kos dan nama pencatat dicetak lewat fungsi e() agar aman dari XSS -->
                        <a href="detail.php?id=<?= (int) $baris['id'] ?>" class="item-card">
                            <div class="card-media">
                                <?php if (!empty($baris['foto_kos']) && is_file(__DIR__ . '/' . $baris['foto_kos'])): ?>
                                    <img src="<?= e($baris['foto_kos']) ?>" alt="Foto <?= e($baris['nama_kos']) ?>">
                                <?php else: ?>
                                    <div class="card-visual-fallback">
                                        <span class="fallback-cat">Foto belum tersedia</span>
                                    </div>
                                <?php endif; ?>

                                <span class="pill-top-left"><?= e($baris['tipe']) ?></span>
                                <span class="pill-top-right <?= $kelasKetersediaan ?>"><?= e($baris['ketersediaan']) ?></span>
                                <?php if ($baris['fasilitas'] === 'AC'): ?>
                                    <span class="pill-bottom-right">AC</span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <h3 class="card-title"><?= e($baris['nama_kos']) ?></h3>
                                <p class="card-desc"><?= ($baris['deskripsi'] ?? '') !== '' ? e($baris['deskripsi']) : 'Belum ada keterangan tambahan dari pengelola.' ?></p>

                                <div class="card-meta-list">
                                    <div class="card-meta-item"><span class="meta-label">Alamat</span><span class="meta-val"><?= e($baris['alamat']) ?></span></div>
                                    <div class="card-meta-item"><span class="meta-label">Jarak</span><span class="meta-val"><?= e(formatJarak((int) $baris['jarak_m'])) ?> dari kampus</span></div>
                                    <div class="card-meta-item"><span class="meta-label">Kamar mandi</span><span class="meta-val"><?= e($baris['kamar_mandi']) ?></span></div>
                                </div>

                                <div class="card-author">
                                    <span class="author-avatar"><?= e($inisial) ?></span>
                                    <span class="author-name">Dicatat <?= e($baris['pencatat']) ?></span>
                                </div>

                                <div class="card-footer">
                                    <div>
                                        <div class="price-label">Per bulan</div>
                                        <div class="price-amount"><?= e(formatRupiah((int) $baris['harga'])) ?></div>
                                    </div>
                                    <span class="card-detail-btn">Detail</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (count($daftar) === 0): ?>
                    <div class="panel-card empty-state">
                        <h3>Belum ada kos tercatat</h3>
                        <p><?= $pengguna['peran'] === 'admin'
                            ? 'Catat kos pertama agar muncul di daftar ini.'
                            : 'Pengelola belum mencatat kos. Cek lagi nanti.' ?></p>
                        <?php if ($pengguna['peran'] === 'admin'): ?>
                            <a href="tambah.php" class="btn">Catat Kos Baru</a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($jumlahTampil === 0): ?>
                    <div class="panel-card empty-state">
                        <h3>Tidak ada kos tipe <?= e($filterTipe) ?></h3>
                        <p>Coba pilih tipe lain, atau tampilkan semua kos.</p>
                        <a href="daftar.php#katalog" class="btn btn-secondary">Tampilkan semua</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <?php footerSitus($pengguna); ?>
    <?php skripNavbar(); ?>
</body>
</html>