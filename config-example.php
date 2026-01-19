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

// Konfigurasi Perusahaan
define('COMPANY_NAME', 'L9kyuu Company');           // Nama perusahaan untuk laporan
define('COMPANY_ICON_PATH', BASE_URL . 'assets/images/company-icon.svg'); // Path ke ikon perusahaan (relatif dari root)

// Konfigurasi Copyright
define('COPYRIGHT_START_YEAR', 2025); // Tahun awal hak cipta

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