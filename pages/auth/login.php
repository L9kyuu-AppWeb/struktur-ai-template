<?php
if (isLoggedIn()) {
    redirect(BASE_URL . 'index.php?page=dashboard');
}

// Get client IP address
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            $ip = trim($ipList[0]);

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Check if IP is blocked due to too many failed attempts
function isIPBlocked($pdo, $ipAddress) {
    $maxAttempts = LOGIN_MAX_ATTEMPTS; // From config
    $timeWindow = LOGIN_ATTEMPT_WINDOW; // From config

    $windowStart = time() - $timeWindow;

    // Count failed attempts within the time window
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as failed_attempts
        FROM failed_login_attempts
        WHERE ip_address = ? AND attempt_time > FROM_UNIXTIME(?)
    ");
    $stmt->execute([$ipAddress, $windowStart]);
    $result = $stmt->fetch();

    $failedAttempts = $result ? $result['failed_attempts'] : 0;

    // Check if the user should be blocked
    if ($failedAttempts >= $maxAttempts) {
        return true;
    }

    return false;
}

// Get block message if IP is blocked
function getBlockMessage($blockEndTime = null) {
    if ($blockEndTime) {
        $formattedTime = date('H:i:s', $blockEndTime);
        $date = date('d M Y', $blockEndTime);
        return [
            'message' => "Terlalu banyak percobaan login gagal. Akun Anda diblokir.",
            'block_end_time' => $blockEndTime,
            'formatted_time' => $formattedTime,
            'formatted_date' => $date
        ];
    } else {
        return [
            'message' => "Terlalu banyak percobaan login gagal. Akun Anda diblokir.",
        ];
    }
}

// Calculate remaining time for block
function getRemainingBlockTime() {
    global $pdo, $ipAddress;

    $maxAttempts = LOGIN_MAX_ATTEMPTS; // From config
    $timeWindow = LOGIN_ATTEMPT_WINDOW; // From config

    $windowStart = time() - $timeWindow;

    // Count failed attempts within the time window
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as failed_attempts
        FROM failed_login_attempts
        WHERE ip_address = ? AND attempt_time > FROM_UNIXTIME(?)
    ");
    $stmt->execute([$ipAddress, $windowStart]);
    $result = $stmt->fetch();

    $failedAttempts = $result ? $result['failed_attempts'] : 0;

    // Check if the user should be blocked
    if ($failedAttempts >= $maxAttempts) {
        // Calculate when the block will end (last failed attempt + block time)
        $stmt = $pdo->prepare("
            SELECT UNIX_TIMESTAMP(attempt_time) as attempt_timestamp
            FROM failed_login_attempts
            WHERE ip_address = ?
            ORDER BY attempt_time DESC
            LIMIT 1
        ");
        $stmt->execute([$ipAddress]);
        $lastAttempt = $stmt->fetch();

        if ($lastAttempt) {
            $lastAttemptTime = $lastAttempt['attempt_timestamp'];
            $blockEndTime = $lastAttemptTime + LOGIN_BLOCK_TIME;
            $remainingTime = $blockEndTime - time();

            if ($remainingTime > 0) {
                return [
                    'remaining' => $remainingTime,
                    'end_time' => $blockEndTime
                ];
            }
        }
    }

    return null;
}

// Record failed login attempt
function recordFailedLoginAttempt($pdo, $ipAddress, $username) {
    $stmt = $pdo->prepare("
        INSERT INTO failed_login_attempts (ip_address, username, attempt_time)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$ipAddress, $username]);

    // Clean up old records outside the window period
    $windowStart = time() - LOGIN_ATTEMPT_WINDOW; // From config
    $cleanupStmt = $pdo->prepare("
        DELETE FROM failed_login_attempts
        WHERE attempt_time < FROM_UNIXTIME(?)
    ");
    $cleanupStmt->execute([$windowStart]);
}

$ipAddress = getClientIP();

// Check if IP is blocked when loading the page
$blockInfo = getRemainingBlockTime();
$isBlocked = ($blockInfo !== null);
$error = $isBlocked ? getBlockMessage()['message'] : '';

// Set remaining time for the countdown if blocked
$remainingTime = $isBlocked ? $blockInfo['remaining'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $password = cleanInput($_POST['password']);
    $captcha_input = cleanInput($_POST['captcha']);
    $captcha_session = $_SESSION['captcha'] ?? '';

    // Check if IP is blocked before processing anything
    $currentBlockInfo = getRemainingBlockTime();
    if ($currentBlockInfo !== null) {
        // Set error message in session to persist after redirect
        $_SESSION['login_error'] = getBlockMessage()['message'];
        $_SESSION['is_blocked'] = true;
        $_SESSION['block_end_time'] = $currentBlockInfo['end_time'];
        redirect(BASE_URL . 'index.php?page=login');
    } else {
        // If not blocked, proceed with validation
        if (strtolower(trim($captcha_input)) !== strtolower(trim($captcha_session))) {
            // Record failed attempt for CAPTCHA error
            recordFailedLoginAttempt($pdo, $ipAddress, $username);

            // Check again if IP is now blocked after recording this attempt
            $newBlockInfo = getRemainingBlockTime();
            if ($newBlockInfo !== null) {
                $_SESSION['login_error'] = getBlockMessage()['message'];
                $_SESSION['is_blocked'] = true;
                $_SESSION['block_end_time'] = $newBlockInfo['end_time'];
                redirect(BASE_URL . 'index.php?page=login');
            } else {
                $_SESSION['login_error'] = 'CAPTCHA tidak valid!';
                redirect(BASE_URL . 'index.php?page=login');
            }
        } elseif (empty($username) || empty($password) || empty($captcha_input)) {
            // Record failed attempt for empty fields
            recordFailedLoginAttempt($pdo, $ipAddress, $username);

            // Check again if IP is now blocked after recording this attempt
            $newBlockInfo = getRemainingBlockTime();
            if ($newBlockInfo !== null) {
                $_SESSION['login_error'] = getBlockMessage()['message'];
                $_SESSION['is_blocked'] = true;
                $_SESSION['block_end_time'] = $newBlockInfo['end_time'];
                redirect(BASE_URL . 'index.php?page=login');
            } else {
                $_SESSION['login_error'] = 'Username, password, dan CAPTCHA harus diisi!';
                redirect(BASE_URL . 'index.php?page=login');
            }
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_active'] == 1) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['last_activity'] = time();

                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // Log activity
                    logActivity($user['id'], 'login', 'User logged in');

                    // Clear failed login attempts for this IP and username
                    $stmt = $pdo->prepare("
                        DELETE FROM failed_login_attempts
                        WHERE ip_address = ? AND username = ?
                    ");
                    $stmt->execute([$ipAddress, $username]);

                    // Clear CAPTCHA session
                    unset($_SESSION['captcha']);

                    redirect(BASE_URL . 'index.php?page=dashboard');
                } else {
                    // Record failed attempt for inactive account
                    recordFailedLoginAttempt($pdo, $ipAddress, $username);

                    // Check again if IP is now blocked after recording this attempt
                    $newBlockInfo = getRemainingBlockTime();
                    if ($newBlockInfo !== null) {
                        $_SESSION['login_error'] = getBlockMessage()['message'];
                        $_SESSION['is_blocked'] = true;
                        $_SESSION['block_end_time'] = $newBlockInfo['end_time'];
                        redirect(BASE_URL . 'index.php?page=login');
                    } else {
                        $_SESSION['login_error'] = 'Akun Anda tidak aktif!';
                        redirect(BASE_URL . 'index.php?page=login');
                    }
                }
            } else {
                // Record failed attempt for wrong credentials
                recordFailedLoginAttempt($pdo, $ipAddress, $username);

                // Check again if IP is now blocked after recording this attempt
                $newBlockInfo = getRemainingBlockTime();
                if ($newBlockInfo !== null) {
                    $_SESSION['login_error'] = getBlockMessage()['message'];
                    $_SESSION['is_blocked'] = true;
                    $_SESSION['block_end_time'] = $newBlockInfo['end_time'];
                    redirect(BASE_URL . 'index.php?page=login');
                } else {
                    $_SESSION['login_error'] = 'Username atau password salah!';
                    redirect(BASE_URL . 'index.php?page=login');
                }
            }
        }
    }
}

// Check for error messages from previous POST request
$error = $_SESSION['login_error'] ?? '';
$isBlocked = $_SESSION['is_blocked'] ?? false;
$blockEndtime = $_SESSION['block_end_time'] ?? null;

// Clear the error session variables to avoid showing them on subsequent page loads
unset($_SESSION['login_error']);
unset($_SESSION['is_blocked']);
unset($_SESSION['block_end_time']);

// If not blocked, check if IP is blocked now (for display on page load)
if (!$isBlocked) {
    $blockInfo = getRemainingBlockTime();
    $isBlocked = ($blockInfo !== null);
    if ($isBlocked) {
        $blockEndtime = $blockInfo['end_time'];
    }
} else {
    // If we're showing a blocked state from session, set the block info for display
    $blockInfo = ['end_time' => $blockEndtime];
}

// Generate CAPTCHA values
$num1 = rand(1, 50);
$num2 = rand(1, 5);
$operation = rand(0, 1); // 0 for addition, 1 for subtraction
$operation_symbol = $operation === 0 ? '+' : '+';
$answer = $operation === 0 ? $num1 + $num2 : $num1 + $num2;

// Store correct answer in session
$_SESSION['captcha'] = $answer;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 to-blue-700 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo SITE_NAME; ?></h1>
                <p class="text-gray-500 mt-2">Masuk ke akun Anda</p>
            </div>

            <?php if ($isBlocked): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4">
                <div id="block-message"><?php echo getBlockMessage()['message']; ?></div>
                <?php if (isset($blockInfo) && isset($blockInfo['end_time'])): ?>
                <div class="mt-2 text-center font-semibold">
                    Waktu saat ini: <span class="text-sm"><?php echo date('d M Y H:i:s'); ?></span><br>
                    Anda dapat mencoba kembali pada: <br>
                    <span class="text-lg"><?php echo date('d M Y H:i:s', $blockInfo['end_time']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif (is_array($error) && isset($error['message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4">
                <?php echo $error['message']; ?>
            </div>
            <?php elseif ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['timeout'])): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-xl mb-4">
                Sesi Anda telah berakhir. Silakan login kembali.
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username atau Email</label>
                    <input type="text" name="username" required
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                           placeholder="Masukkan username atau email">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                           placeholder="Masukkan password">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CAPTCHA</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="flex-1 flex items-center justify-center bg-gray-100 rounded-xl h-12 border border-gray-200 font-bold text-lg text-gray-800 min-h-[48px]">
                            <?php echo "$num1 $operation_symbol $num2 = ?"; ?>
                        </div>
                        <input type="text" name="captcha" required
                               class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition flex-1 min-w-[150px]"
                               placeholder="Jawaban">
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Masukkan hasil dari perhitungan di atas</p>
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors <?php echo $isBlocked ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                        <?php echo $isBlocked ? 'disabled' : ''; ?>>
                    Masuk
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Demo Login: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>
        </div>
    </div>

    <script>
        // Function to refresh CAPTCHA
        function refreshCaptcha() {
            location.reload();
        }
    </script>
</body>
</html>