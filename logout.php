<?php
// Proses logout: bersihkan data session, hapus cookie sesi, lalu arahkan ke halaman login
require_once __DIR__ . '/sesi.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

header('Location: login.php?keluar=1');
exit;