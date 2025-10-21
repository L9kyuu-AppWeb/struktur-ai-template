<?php
// Check permission - only admin can delete users
if (!hasRole('admin')) {
    require_once __DIR__ . '/../errors/403.php';
    exit;
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    redirect('index.php?page=users');
}

// Cannot delete yourself
if ($userId == $_SESSION['user_id']) {
    setAlert('error', 'Anda tidak dapat menghapus akun Anda sendiri!');
    redirect('index.php?page=users');
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setAlert('error', 'User tidak ditemukan!');
    redirect('index.php?page=users');
}

// Delete user
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
if ($stmt->execute([$userId])) {
    logActivity($_SESSION['user_id'], 'delete_user', "Deleted user: " . $user['username']);
    setAlert('success', 'User berhasil dihapus!');
} else {
    setAlert('error', 'Gagal menghapus user!');
}

redirect('index.php?page=users');
?>