<?php
$title = "My Notifications";
require_once 'includes/header.php';
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<div class="card">
    <div class="card-header bg-black text-white">Notifications</div>
    <div class="card-body">
        <?php if ($notifications->num_rows == 0): ?>
            <p class="text-muted">No notifications.</p>
        <?php else: ?>
            <div class="list-group">
                <?php while($notif = $notifications->fetch_assoc()): ?>
                    <a href="mark_notification_read.php?id=<?= $notif['id'] ?>&redirect=<?= urlencode($notif['link'] ?? 'notifications.php') ?>" class="list-group-item list-group-item-action <?= $notif['is_read'] ? '' : 'list-group-item-primary' ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?= htmlspecialchars($notif['title']) ?></h5>
                            <small><?= $notif['created_at'] ?></small>
                        </div>
                        <p class="mb-1"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>