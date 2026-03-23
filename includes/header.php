<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$user = isLoggedIn() ? currentUser($conn) : null;
$csrf = generateCSRF();

// Count unread messages for nav badge
$unreadCount = 0;
$unreadNotifs = 0;
if ($user) {
    $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM messages WHERE receiver_id = ? AND read_status = 0');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $unreadCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);

    $nStmt = $conn->prepare('SELECT COUNT(*) as cnt FROM sighting_notifications WHERE user_id = ? AND is_read = 0');
    $nStmt->bind_param('i', $user['id']);
    $nStmt->execute();
    $unreadNotifs = (int)($nStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nauti-Connect – The boater community platform for anchorages, parts, wildlife sightings, and more.">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?>Nauti-Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#0a1628',
                        ocean: '#1e3a5f',
                        gold: '#c9a227',
                        'gold-light': '#d4af37',
                        'navy-mid': '#0f2340',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#0a1628] text-white min-h-screen">

<!-- Navigation -->
<nav class="bg-[#0a1628]/95 backdrop-blur border-b border-[#c9a227]/20 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="<?= SITE_URL ?>/" class="flex items-center gap-2 flex-shrink-0">
                <svg class="w-8 h-8 text-[#c9a227]" viewBox="0 0 40 40" fill="currentColor" aria-hidden="true">
                    <path d="M20 2L4 32h32L20 2zm0 6l11 20H9L20 8z"/>
                    <path d="M17 34c0 1.1.9 2 2 2h2c1.1 0 2-.9 2-2v-2h-6v2z"/>
                    <rect x="10" y="34" width="20" height="3" rx="1.5"/>
                </svg>
                <span class="text-xl font-bold text-[#c9a227]">Nauti-Connect</span>
            </a>

            <!-- Desktop Nav Links -->
            <div class="hidden md:flex items-center gap-1">
                <a href="<?= SITE_URL ?>/" class="nav-link <?= $currentPage === 'index.php' && dirname($_SERVER['PHP_SELF']) === '/' ? 'active' : '' ?>">Dashboard</a>
                <a href="<?= SITE_URL ?>/parts/" class="nav-link">Parts</a>
                <a href="<?= SITE_URL ?>/boats/" class="nav-link">Boats</a>
                <a href="<?= SITE_URL ?>/anchorages/" class="nav-link">Anchorages</a>
                <a href="<?= SITE_URL ?>/sightings/" class="nav-link">Sightings</a>
                <a href="<?= SITE_URL ?>/charts/" class="nav-link">Charts</a>
                <a href="<?= SITE_URL ?>/builds/" class="nav-link">Builds</a>
                <a href="<?= SITE_URL ?>/maintenance/" class="nav-link">Guides</a>
            </div>

            <!-- Right side -->
            <div class="hidden md:flex items-center gap-3">
                <?php if ($user): ?>
                    <a href="<?= SITE_URL ?>/notifications/" class="relative nav-link" title="Sighting Alerts">
                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <?php if ($unreadNotifs > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-[#c9a227] text-[#0a1628] text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold"><?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= SITE_URL ?>/messages/" class="relative nav-link">
                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= SITE_URL ?>/profile/" class="flex items-center gap-2 nav-link">
                        <?php if ($user['profile_img']): ?>
                            <img src="<?= UPLOAD_URL . sanitize($user['profile_img']) ?>" class="w-7 h-7 rounded-full object-cover border border-[#c9a227]/50" alt="Profile">
                        <?php else: ?>
                            <div class="w-7 h-7 rounded-full bg-[#1e3a5f] border border-[#c9a227]/50 flex items-center justify-center text-xs font-bold text-[#c9a227]">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="text-sm"><?= sanitize($user['username']) ?></span>
                    </a>
                    <a href="<?= SITE_URL ?>/auth/logout.php" class="btn-gold text-sm px-3 py-1.5">Logout</a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/auth/login.php" class="nav-link">Login</a>
                    <a href="<?= SITE_URL ?>/auth/register.php" class="btn-gold text-sm px-4 py-2">Join</a>
                <?php endif; ?>
            </div>

            <!-- Mobile hamburger -->
            <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg text-gray-300 hover:text-[#c9a227] focus:outline-none" aria-label="Toggle menu">
                <svg id="hamburger-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-[#0f2340] border-t border-[#c9a227]/20">
        <div class="px-4 py-3 space-y-1">
            <a href="<?= SITE_URL ?>/" class="mobile-nav-link">Dashboard</a>
            <a href="<?= SITE_URL ?>/parts/" class="mobile-nav-link">Parts Marketplace</a>
            <a href="<?= SITE_URL ?>/boats/" class="mobile-nav-link">Boat Sales</a>
            <a href="<?= SITE_URL ?>/anchorages/" class="mobile-nav-link">Anchorages</a>
            <a href="<?= SITE_URL ?>/sightings/" class="mobile-nav-link">Wildlife Sightings</a>
            <a href="<?= SITE_URL ?>/charts/" class="mobile-nav-link">Charts</a>
            <a href="<?= SITE_URL ?>/builds/" class="mobile-nav-link">Build Logs</a>
            <a href="<?= SITE_URL ?>/maintenance/" class="mobile-nav-link">Maintenance Guides</a>
            <?php if ($user): ?>
                <a href="<?= SITE_URL ?>/notifications/" class="mobile-nav-link">🔔 Sighting Alerts <?= $unreadNotifs > 0 ? "($unreadNotifs)" : '' ?></a>
                <a href="<?= SITE_URL ?>/messages/" class="mobile-nav-link">Messages <?= $unreadCount > 0 ? "($unreadCount)" : '' ?></a>
                <a href="<?= SITE_URL ?>/profile/" class="mobile-nav-link">Profile</a>
                <a href="<?= SITE_URL ?>/auth/logout.php" class="mobile-nav-link text-red-400">Logout</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/auth/login.php" class="mobile-nav-link">Login</a>
                <a href="<?= SITE_URL ?>/auth/register.php" class="mobile-nav-link text-[#c9a227] font-bold">Join Free</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Flash messages -->
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="flash-message bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 mx-4 mt-4 rounded-lg max-w-7xl mx-auto" role="alert">
        <?= sanitize($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="flash-message bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 mx-4 mt-4 rounded-lg max-w-7xl mx-auto" role="alert">
        <?= sanitize($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
