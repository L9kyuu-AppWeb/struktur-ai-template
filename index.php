<?php
require_once 'config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? cleanInput($_GET['page']) : 'dashboard';
$allowedPages = ['dashboard', 'profile', 'users', 'settings', 'login', 'logout'];
$publicPages   = ['login']; // Halaman yang tidak butuh login

// Validasi halaman
if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

// 1️⃣ Jika halaman bukan “public”, maka lakukan cek login terlebih dahulu
if (!in_array($page, $publicPages)) {
    require_once 'includes/auth_check.php'; // DI SINI BOLEH REDIRECT (header) karena belum ada HTML
}

// 2️⃣ Setelah login dicek, baru tampilkan header & sidebar
if (!in_array($page, $publicPages)) {
    require_once 'includes/header.php';  // Mulai HTML
    require_once 'includes/sidebar.php';
    echo '<main class="lg:ml-64 pt-16 min-h-screen flex flex-col"><div class="p-6 flex-1">';
}

// 3️⃣ Routing halaman
switch ($page) {
    case 'login':
        require_once 'pages/auth/login.php';
        break;
    case 'logout':
        require_once 'pages/auth/logout.php';
        break;
    case 'dashboard':
        require_once 'pages/dashboard/index.php';
        break;
    case 'profile':
        require_once 'pages/profile/index.php';
        break;
    case 'users':
        require_once 'pages/users/index.php';
        break;
    case 'settings':
        require_once 'pages/settings/index.php';
        break;
    default:
        echo '<h1>404 - Page Not Found</h1>';
}

// 4️⃣ Tutup layout untuk halaman yang sudah login
if (!in_array($page, $publicPages)) {
    echo '</div>';
    require_once 'includes/footer.php';
}
?>
