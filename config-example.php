<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Ganti dengan user database Anda
define('DB_PASS', '');     // Ganti dengan password database Anda
define('DB_NAME', 'admin_panel_db');         // Ganti dengan nama database Anda

// Konfigurasi Aplikasi
define('SITE_NAME', 'L9kyuuPanel');
define('BASE_URL', 'http://localhost/struktur-ai-template/'); // Ganti dengan URL dasar proyek Anda
define('UPLOAD_PATH', __DIR__ . '/assets/uploads/');

// Konfigurasi Session
define('SESSION_TIMEOUT', 3600); // 1 hour

// Konfigurasi Limit Login
define('LOGIN_MAX_ATTEMPTS', 5);        // Jumlah maksimal percobaan login sebelum blokir
define('LOGIN_BLOCK_TIME', 900);        // Waktu blokir dalam detik (15 menit)
define('LOGIN_ATTEMPT_WINDOW', 900);    // Jendela waktu untuk menghitung percobaan gagal (15 menit)

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}