<?php
// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Check user role
function hasRole($roleNames) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    if (is_array($roleNames)) {
        return in_array($user['role_name'], $roleNames);
    }
    return $user['role_name'] === $roleNames;
}

// Sanitize input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Log activity
function logActivity($userId, $type, $description) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, activity_type, description, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $type, $description, $_SERVER['REMOTE_ADDR']]);
}

// Format date Indonesia
function formatDate($date) {
    return date('d F Y H:i', strtotime($date));
}

// Get avatar URL
function getAvatarUrl($avatar) {
    if (empty($avatar) || $avatar === 'default-avatar.png') {
        return 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username'] ?? 'User') . '&background=3b82f6&color=fff';
    }
    return BASE_URL . 'assets/uploads/avatars/' . $avatar;
}

// Alert message
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}
?>