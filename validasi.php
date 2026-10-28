<?php
// Daftar nilai yang diizinkan (whitelist) sesuai kolom ENUM di tabel kos
const DAFTAR_TIPE         = ['Putra', 'Putri', 'Campur'];
const DAFTAR_FASILITAS    = ['Kipas angin', 'AC'];
const DAFTAR_KAMAR_MANDI  = ['Dalam', 'Luar'];
const DAFTAR_KETERSEDIAAN = ['Tersedia', 'Penuh'];

// Konstanta batas angka untuk validasi
const HARGA_MIN       = 200000;
const HARGA_MAX       = 5000000;
const HARGA_MIN_AC    = 500000;
const JARAK_MAKS_M    = 20000;

// Hapus karakter kontrol dan spasi berlebih di awal/akhir input.
// Tag HTML sengaja tidak dihapus di sini karena sudah di-escape dengan e() saat ditampilkan.
function bersihkanTeks(string $teks): string
{
    $teks = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $teks) ?? '';
    return trim($teks);
}

// Ambil nilai string dari $_POST dan langsung rapikan spasinya
function ambilPost(string $kunci): string
{
    $nilai = $_POST[$kunci] ?? '';
    return is_string($nilai) ? bersihkanTeks($nilai) : '';
}

function formatRupiah(int $nominal): string
{
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

function formatJarak(int $meter): string
{
    if ($meter < 1000) {
        return $meter . ' m';
    }
    return number_format($meter / 1000, 1, ',', '.') . ' km';
}

// Validasi wajib isi dan panjang karakter (min - max)
function validasiPanjang(string $label, string $nilai, int $min, int $max): ?string
{
    if ($nilai === '') {
        return "$label wajib diisi.";
    }
    $panjang = mb_strlen($nilai);
    if ($panjang < $min || $panjang > $max) {
        return "$label harus terdiri dari $min sampai $max karakter (saat ini $panjang karakter).";
    }
    return null;
}

// Validasi whitelist untuk input <select> / ENUM.
// Nilai dicek ulang di server dengan in_array(..., true) karena opsi HTML bisa diubah lewat DevTools.
function validasiPilihan(string $label, string $nilai, array $daftarSah): ?string
{
    if ($nilai === '') {
        return "$label wajib dipilih.";
    }
    if (!in_array($nilai, $daftarSah, true)) {
        return "Pilihan $label tidak sah.";
    }
    return null;
}

// Validasi input angka bulat dan rentang nilainya
function validasiAngka(string $label, string $nilai, int $min, int $maks): ?string
{
    if ($nilai === '') {
        return "$label wajib diisi.";
    }
    // Hanya terima karakter digit 0-9 (tolak huruf, minus, titik, atau koma)
    if (!ctype_digit($nilai)) {
        return "$label harus berupa angka bulat tanpa titik, koma, atau huruf.";
    }
    $angka = (int) $nilai;
    if ($angka < $min || $angka > $maks) {
        return "$label harus antara " . number_format($min, 0, ',', '.') . ' dan ' . number_format($maks, 0, ',', '.') . '.';
    }
    return null;
}

// Validasi khusus 1 (silang antar kolom): jika fasilitas = AC, harga minimal Rp 500.000
function validasiHargaAc(string $fasilitas, string $harga): ?string
{
    if ($fasilitas === 'AC' && ctype_digit($harga) && (int) $harga < HARGA_MIN_AC) {
        return 'Kos berfasilitas AC minimal ' . formatRupiah(HARGA_MIN_AC) . ' per bulan (kamu mengisi ' . formatRupiah((int) $harga) . ').';
    }
    return null;
}

// Validasi khusus 2 (silang antar kolom): jika ketersediaan = Penuh, deskripsi wajib diisi minimal 10 karakter
function validasiDeskripsi(string $ketersediaan, string $deskripsi): ?string
{
    $panjang = mb_strlen($deskripsi);
    if ($panjang > 2000) {
        return "Deskripsi maksimal 2000 karakter (saat ini $panjang karakter).";
    }
    if ($ketersediaan === 'Penuh' && $panjang < 10) {
        return "Kos berstatus Penuh wajib memiliki deskripsi minimal 10 karakter, misalnya perkiraan kapan kamar kosong (saat ini $panjang karakter).";
    }
    return null;
}

// Validasi dan simpan file foto kos yang diunggah
function prosesUnggahFoto(string $kunciFile, array &$errors): ?string
{
    if (!isset($_FILES[$kunciFile]) || $_FILES[$kunciFile]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // Foto boleh dikosongkan
    }

    $file = $_FILES[$kunciFile];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[$kunciFile] = 'Gagal mengunggah gambar (pastikan ukuran file di bawah 2 MB).';
        return null;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $errors[$kunciFile] = 'Ukuran gambar terlalu besar. Maksimal 2 MB.';
        return null;
    }

    // Cek MIME type langsung dari isi file memakai finfo, bukan dari ekstensi nama file
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $daftarMimeSah = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!array_key_exists($mime, $daftarMimeSah)) {
        $errors[$kunciFile] = 'Format gambar tidak didukung. Gunakan file JPG, PNG, atau WEBP.';
        return null;
    }

    $folderTujuan = __DIR__ . '/uploads';
    if (!is_dir($folderTujuan)) {
        mkdir($folderTujuan, 0755, true);
    }

    // Ganti nama file dengan nilai acak supaya aman dan tidak menimpa file lain
    $namaBaru    = 'tkos_' . bin2hex(random_bytes(8)) . '.' . $daftarMimeSah[$mime];
    $jalurSimpan = $folderTujuan . '/' . $namaBaru;

    if (!move_uploaded_file($file['tmp_name'], $jalurSimpan)) {
        $errors[$kunciFile] = 'Gagal menyimpan file gambar ke server.';
        return null;
    }

    return 'uploads/' . $namaBaru;
}