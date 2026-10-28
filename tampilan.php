<?php
// tampilan.php
// Potongan tampilan yang dipakai bersama oleh halaman daftar, detail, dan tambah:
// navbar utama dan footer. Semua nilai dinamis dibungkus e().

// Navbar utama. $aktif menandai menu yang sedang dibuka: 'beranda', 'katalog', 'solusi', 'tambah'.
function topbarUtama(array $pengguna, string $aktif = ''): void
{
    $kelas = fn(string $nama): string => $aktif === $nama ? 'nav-pill-item active' : 'nav-pill-item';
    ?>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="daftar.php" class="brand">
                <img class="brand-logo" src="assets/logo-temukos.svg" alt="" width="38" height="38">
                <span>TemuKos</span>
            </a>

            <nav class="nav-pills" id="mainNav" aria-label="Navigasi utama">
                <a href="daftar.php" data-nav="beranda" class="<?= $kelas('beranda') ?>">Beranda</a>
                <a href="daftar.php#katalog" data-nav="katalog" class="<?= $kelas('katalog') ?>">Daftar Kos</a>
                <a href="daftar.php#solusi" data-nav="solusi" class="<?= $kelas('solusi') ?>">Info Layanan</a>
                <?php if ($pengguna['peran'] === 'admin'): ?>
                    <a href="tambah.php" class="<?= $kelas('tambah') ?>">+ Catat Kos</a>
                <?php endif; ?>
            </nav>

            <div class="user-actions">
                <span class="user-badge">
                    <?= e($pengguna['nama']) ?>
                    <span class="role-tag"><?= e($pengguna['peran']) ?></span>
                </span>
                <a href="logout.php" class="btn btn-secondary">Keluar</a>
            </div>
        </div>
    </header>
    <?php
}

function footerSitus(array $pengguna): void
{
    ?>
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-col brand-col">
                <div class="brand">
                    <img class="brand-logo" src="assets/logo-temukos.svg" alt="" width="38" height="38">
                    <span>TemuKos</span>
                </div>
                <p class="footer-desc">
                    Katalog kos sekitar Universitas Jenderal Soedirman. Harga, fasilitas, jarak ke kampus, dan ketersediaan kamar dalam satu daftar.
                </p>
                <p class="footer-copyright">Mini project Pemrograman Web I (IF21305)</p>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Navigasi</h4>
                <ul class="footer-links">
                    <li><a href="daftar.php">Beranda</a></li>
                    <li><a href="daftar.php#katalog">Daftar Kos</a></li>
                    <li><a href="daftar.php#solusi">Info Layanan</a></li>
                    <?php if ($pengguna['peran'] === 'admin'): ?>
                        <li><a href="tambah.php">Catat Kos Baru</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Tentang</h4>
                <ul class="footer-links">
                    <li><span class="footer-text">Program Studi Informatika</span></li>
                    <li><span class="footer-text">Fakultas Teknik</span></li>
                    <li><span class="footer-text">Universitas Jenderal Soedirman</span></li>
                </ul>
            </div>
        </div>
    </footer>
    <?php
}

// Skrip kecil: menandai menu navbar yang diklik atau sesuai hash URL (#katalog / #solusi)
function skripNavbar(): void
{
    ?>
    <script>
        (function () {
            const navItems = document.querySelectorAll('#mainNav .nav-pill-item');
            function tandaiDariHash() {
                const peta = { '#katalog': 'katalog', '#solusi': 'solusi' };
                const kunci = peta[window.location.hash];
                if (!kunci) return;
                navItems.forEach(el => el.classList.toggle('active', el.dataset.nav === kunci));
            }
            navItems.forEach(item => item.addEventListener('click', function () {
                if (this.dataset.nav) {
                    navItems.forEach(el => el.classList.remove('active'));
                    this.classList.add('active');
                }
            }));
            window.addEventListener('hashchange', tandaiDariHash);
            tandaiDariHash();
        })();
    </script>
    <?php
}
