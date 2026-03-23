<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

if (isLoggedIn()) redirect(SITE_URL . '/');

$errors = [];
$boatTypes = ['Sailboat', 'Motorboat', 'Catamaran', 'Trimaran', 'Trawler', 'Center Console', 'Kayak/Canoe', 'Inflatable/RIB', 'Houseboat', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRF($token)) {
        $errors[] = 'Invalid request token.';
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $boatType  = trim($_POST['boat_type'] ?? '');
        $bio       = trim($_POST['bio'] ?? '');

        if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be 3–50 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username may only contain letters, numbers, and underscores.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            // Check uniqueness
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'Username or email already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $conn->prepare('INSERT INTO users (username, email, password_hash, boat_type, bio) VALUES (?, ?, ?, ?, ?)');
                $ins->bind_param('sssss', $username, $email, $hash, $boatType, $bio);
                if ($ins->execute()) {
                    session_regenerate_id(true);
                    $_SESSION['user_id']  = $conn->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['flash_success'] = "Welcome aboard, $username!";
                    redirect(SITE_URL . '/');
                } else {
                    $errors[] = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$csrf      = generateCSRF();
$pageTitle = 'Join Nauti-Connect';
include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-screen flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-lg">
        <div class="glass-card p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white">Join the Fleet</h1>
                <p class="text-gray-400 mt-2">Create your Nauti-Connect account</p>
            </div>

            <?php if ($errors): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-6" role="alert">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" id="username" name="username" required
                               class="form-input" placeholder="sea_captain"
                               value="<?= sanitize($_POST['username'] ?? '') ?>"
                               pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" id="email" name="email" required
                               class="form-input" placeholder="captain@sea.com"
                               value="<?= sanitize($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" id="password" name="password" required
                               class="form-input" placeholder="Min 8 characters" minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="form-input" placeholder="Repeat password">
                    </div>
                </div>

                <div class="form-group">
                    <label for="boat_type" class="form-label">Boat Type</label>
                    <select id="boat_type" name="boat_type" class="form-input">
                        <option value="">Select your vessel type…</option>
                        <?php foreach ($boatTypes as $bt): ?>
                            <option value="<?= sanitize($bt) ?>" <?= ($_POST['boat_type'] ?? '') === $bt ? 'selected' : '' ?>><?= sanitize($bt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bio" class="form-label">Short Bio</label>
                    <textarea id="bio" name="bio" rows="3" class="form-input resize-none" placeholder="Tell the fleet a bit about yourself and your sailing adventures…" maxlength="500"><?= sanitize($_POST['bio'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-gold w-full py-3 text-lg mt-2">
                    Join the Fleet →
                </button>
            </form>

            <p class="text-center text-gray-400 mt-6 text-sm">
                Already a member?
                <a href="<?= SITE_URL ?>/auth/login.php" class="text-[#c9a227] hover:text-[#d4af37] font-semibold">Sign in</a>
            </p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
