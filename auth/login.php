<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (isLoggedIn()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect' => SITE_URL . '/']);
        exit;
    }
    redirect(SITE_URL . '/');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRF($token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $stmt = $conn->prepare('SELECT id, username, password_hash, email FROM users WHERE email = ? OR username = ? LIMIT 1');
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['flash_success'] = 'Welcome back, ' . $user['username'] . '!';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => SITE_URL . '/']);
                    exit;
                }
                $redirect = $_GET['redirect'] ?? SITE_URL . '/';
                redirect(filter_var($redirect, FILTER_SANITIZE_URL));
            } else {
                $error = 'Invalid credentials. Please try again.';
            }
        }
    }
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $error, 'new_csrf' => generateCSRF()]);
        exit;
    }
}

$csrf = generateCSRF();
$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md">
        <div class="glass-card p-8">
            <div class="text-center mb-8">
                <svg class="w-16 h-16 text-[#c9a227] mx-auto mb-4" viewBox="0 0 40 40" fill="currentColor">
                    <path d="M20 2L4 32h32L20 2zm0 6l11 20H9L20 8z"/>
                    <rect x="10" y="34" width="20" height="3" rx="1.5"/>
                </svg>
                <h1 class="text-3xl font-bold text-white">Welcome Back</h1>
                <p class="text-gray-400 mt-2">Sign in to Nauti-Connect</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-6" role="alert">
                    <?= sanitize($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">

                <div class="form-group">
                    <label for="identifier" class="form-label">Email or Username</label>
                    <input type="text" id="identifier" name="identifier" required autocomplete="username"
                           class="form-input" placeholder="captain@sea.com"
                           value="<?= sanitize($_POST['identifier'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="form-input" placeholder="••••••••">
                </div>

                <button type="submit" class="btn-gold w-full py-3 text-lg mt-2">
                    Set Sail →
                </button>
            </form>

            <p class="text-center text-gray-400 mt-6 text-sm">
                Don't have an account?
                <a href="<?= SITE_URL ?>/auth/register.php" class="text-[#c9a227] hover:text-[#d4af37] font-semibold">Join the fleet</a>
            </p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
