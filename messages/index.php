<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$pageTitle = 'Messages';
$userId    = $_SESSION['user_id'];

// Get conversations grouped by the other user
$stmt = $conn->prepare("
    SELECT m.*, 
           CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
           u.username as other_username,
           u.profile_img as other_img,
           COUNT(CASE WHEN m.receiver_id = ? AND m.read_status = 0 THEN 1 END) as unread
    FROM messages m
    JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id, u.username, u.profile_img
    ORDER BY MAX(m.sent_at) DESC
");
$stmt->bind_param('iiiii', $userId, $userId, $userId, $userId, $userId);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-white mb-8">Messages</h1>

    <?php if (empty($conversations)): ?>
        <div class="text-center py-20">
            <div class="text-6xl mb-4">💬</div>
            <p class="text-gray-400">No messages yet. Connect with sellers and boaters!</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($conversations as $conv): ?>
                <a href="<?= SITE_URL ?>/messages/conversation.php?user=<?= $conv['other_user_id'] ?>"
                   class="flex items-center gap-4 glass-card p-4 hover:border-[#c9a227]/50 transition-all <?= $conv['unread'] > 0 ? 'border-[#c9a227]/30' : '' ?>">
                    <?php if ($conv['other_img']): ?>
                        <img src="<?= UPLOAD_URL . sanitize($conv['other_img']) ?>" class="w-12 h-12 rounded-full object-cover border border-[#c9a227]/30 flex-shrink-0">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-[#1e3a5f] border border-[#c9a227]/30 flex items-center justify-center font-bold text-[#c9a227] text-xl flex-shrink-0">
                            <?= strtoupper(substr($conv['other_username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-white"><?= sanitize($conv['other_username']) ?></p>
                            <span class="text-gray-500 text-xs"><?= timeAgo($conv['sent_at']) ?></span>
                        </div>
                        <p class="text-gray-400 text-sm truncate mt-0.5"><?= sanitize(substr($conv['content'], 0, 60)) ?>…</p>
                    </div>
                    <?php if ($conv['unread'] > 0): ?>
                        <span class="bg-[#c9a227] text-[#0a1628] text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0"><?= $conv['unread'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
