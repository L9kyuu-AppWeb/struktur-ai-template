<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Ganti dengan user database Anda
define('DB_PASS', 'masukkan_password_db_anda');     // Ganti dengan password database Anda
define('DB_NAME', 'masukkan_nama_db_anda');         // Ganti dengan nama database Anda

// Konfigurasi Aplikasi
define('SITE_NAME', 'L9kyuuPanel');
define('BASE_URL', 'http://localhost/nama_proyek_anda/'); // Ganti dengan URL dasar proyek Anda
define('UPLOAD_PATH', __DIR__ . '/assets/uploads/');

// Konfigurasi Session
define('SESSION_TIMEOUT', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}