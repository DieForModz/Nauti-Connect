<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$myId       = $_SESSION['user_id'];
$otherId    = (int)($_GET['user'] ?? 0);
$listingType = trim($_GET['listing_type'] ?? '');
$listingId  = (int)($_GET['listing_id'] ?? 0);

if (!$otherId || $otherId === $myId) redirect(SITE_URL . '/messages/');

// Get other user info
$stmt = $conn->prepare('SELECT id, username, profile_img FROM users WHERE id = ?');
$stmt->bind_param('i', $otherId);
$stmt->execute();
$other = $stmt->get_result()->fetch_assoc();
if (!$other) redirect(SITE_URL . '/messages/');

$pageTitle = 'Chat with ' . sanitize($other['username']);
$errors    = [];

// Handle send
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $content = trim($_POST['content'] ?? '');
        if (empty($content)) {
            $errors[] = 'Message cannot be empty.';
        } elseif (!checkRateLimit($myId, 'message', 60, $conn)) {
            $errors[] = 'Please wait before sending another message (rate limit: 1/minute).';
        } else {
            $lType = $listingType ?: null;
            $lId   = $listingId ?: null;
            $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, listing_type, listing_id, content) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iisis', $myId, $otherId, $lType, $lId, $content);
            $stmt->execute();
            redirect(SITE_URL . '/messages/conversation.php?user=' . $otherId);
        }
    }
}

// Mark messages from other user as read
$markRead = $conn->prepare('UPDATE messages SET read_status = 1 WHERE sender_id = ? AND receiver_id = ? AND read_status = 0');
$markRead->bind_param('ii', $otherId, $myId);
$markRead->execute();

// Get thread messages
$mStmt = $conn->prepare("
    SELECT m.*, 
           s.username as sender_name, s.profile_img as sender_img,
           r.username as receiver_name
    FROM messages m
    JOIN users s ON s.id = m.sender_id
    JOIN users r ON r.id = m.receiver_id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.sent_at ASC
    LIMIT 200
");
$mStmt->bind_param('iiii', $myId, $otherId, $otherId, $myId);
$mStmt->execute();
$thread = $mStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-3xl mx-auto px-4 py-10">
    <div class="glass-card overflow-hidden">
        <!-- Thread header -->
        <div class="flex items-center gap-3 p-4 border-b border-[#c9a227]/20 bg-[#0f2340]">
            <a href="<?= SITE_URL ?>/messages/" class="text-gray-400 hover:text-white">←</a>
            <?php if ($other['profile_img']): ?>
                <img src="<?= UPLOAD_URL . sanitize($other['profile_img']) ?>" class="w-10 h-10 rounded-full object-cover border border-[#c9a227]/40">
            <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-[#1e3a5f] border border-[#c9a227]/40 flex items-center justify-center font-bold text-[#c9a227]">
                    <?= strtoupper(substr($other['username'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div>
                <p class="font-bold text-white"><?= sanitize($other['username']) ?></p>
                <?php if ($listingType && $listingId): ?>
                    <p class="text-xs text-gray-400">Re: <?= sanitize($listingType) ?> #<?= $listingId ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages -->
        <div id="msg-thread" class="p-4 space-y-4 min-h-64 max-h-[60vh] overflow-y-auto bg-[#0a1628]/40">
            <?php if (empty($thread)): ?>
                <p class="text-gray-500 text-center text-sm">No messages yet. Start the conversation!</p>
            <?php else: ?>
                <?php foreach ($thread as $msg):
                    $isMine = $msg['sender_id'] === $myId;
                ?>
                    <div class="flex <?= $isMine ? 'justify-end' : 'justify-start' ?> gap-2">
                        <?php if (!$isMine && $other['profile_img']): ?>
                            <img src="<?= UPLOAD_URL . sanitize($other['profile_img']) ?>" class="w-7 h-7 rounded-full object-cover flex-shrink-0 mt-1">
                        <?php elseif (!$isMine): ?>
                            <div class="w-7 h-7 rounded-full bg-[#1e3a5f] flex items-center justify-center text-xs font-bold text-[#c9a227] flex-shrink-0 mt-1">
                                <?= strtoupper(substr($other['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="max-w-xs sm:max-w-sm">
                            <div class="<?= $isMine ? 'bg-[#c9a227]/20 border-[#c9a227]/30 rounded-br-sm' : 'bg-[#1e3a5f]/60 border-white/10 rounded-bl-sm' ?> border px-4 py-2.5 rounded-2xl text-sm text-white">
                                <?= sanitize($msg['content']) ?>
                            </div>
                            <p class="text-gray-600 text-xs mt-0.5 <?= $isMine ? 'text-right' : 'text-left' ?>"><?= timeAgo($msg['sent_at']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reply form -->
        <div class="p-4 border-t border-[#c9a227]/20 bg-[#0f2340]">
            <?php if ($errors): ?>
                <p class="text-red-400 text-sm mb-2"><?= sanitize($errors[0]) ?></p>
            <?php endif; ?>
            <form method="POST" class="flex gap-3">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">
                <input type="hidden" name="listing_type" value="<?= sanitize($listingType) ?>">
                <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                <input type="text" name="content" class="form-input flex-1" placeholder="Type a message…" required maxlength="2000" autofocus>
                <button type="submit" class="btn-gold px-5">Send</button>
            </form>
        </div>
    </div>
</main>

<script>
// Scroll to bottom of thread
const thread = document.getElementById('msg-thread');
if (thread) thread.scrollTop = thread.scrollHeight;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
