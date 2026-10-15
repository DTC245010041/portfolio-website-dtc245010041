<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
admin_require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        $error = 'ID tin nhắn không hợp lệ.';
    } elseif ($action === 'read') {
        $stmt = $pdo->prepare('UPDATE messages SET is_read=1 WHERE id=:id');
        $stmt->execute(['id' => $id]);
        header('Location: messages.php?done=read');
        exit;
    } elseif ($action === 'unread') {
        $stmt = $pdo->prepare('UPDATE messages SET is_read=0 WHERE id=:id');
        $stmt->execute(['id' => $id]);
        header('Location: messages.php?done=unread');
        exit;
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM messages WHERE id=:id');
        $stmt->execute(['id' => $id]);
        header('Location: messages.php?done=deleted');
        exit;
    } else {
        $error = 'Thao tác không hợp lệ.';
    }
}

$messages = $pdo->query('SELECT id, sender_name, sender_email, subject, content, is_read, created_at FROM messages ORDER BY created_at DESC, id DESC')->fetchAll();
function me(mixed $v): string { return admin_escape((string) ($v ?? '')); }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tin nhắn liên hệ</title>
<style>
body{font:16px system-ui;background:#0b1120;color:#e5e7eb;margin:0;padding:24px}main{max-width:1000px;margin:auto}.item{background:#111827;border:1px solid #334155;border-radius:12px;padding:18px;margin:14px 0}.unread{border-color:#38bdf8}.muted{color:#9ca3af}button,.btn{padding:9px 12px;border:0;border-radius:6px;background:#38bdf8;color:#082f49;cursor:pointer;text-decoration:none;display:inline-block;margin:3px}.danger{background:#7f1d1d;color:#fee2e2}.secondary{background:#334155;color:#e5e7eb}a{color:#7dd3fc}.content{white-space:pre-wrap;overflow-wrap:anywhere}.error{color:#fecaca}
</style></head><body><main>
<h1>Tin nhắn liên hệ (<?= count($messages) ?>)</h1><p><a href="dashboard.php">← Trang tổng quan</a></p>
<?php if ($error): ?><p class="error"><?= me($error) ?></p><?php endif; ?>
<?php if (isset($_GET['done'])): ?><p>Đã xử lý thao tác thành công.</p><?php endif; ?>
<?php if (!$messages): ?><p class="muted">Chưa có tin nhắn nào.</p><?php endif; ?>
<?php foreach ($messages as $m): ?><article class="item <?= (int) $m['is_read'] === 0 ? 'unread' : '' ?>">
<h2><?= me($m['subject'] ?: '(Không có chủ đề)') ?></h2>
<p><strong>Người gửi:</strong> <?= me($m['sender_name']) ?></p>
<p><strong>Email:</strong> <a href="mailto:<?= me($m['sender_email']) ?>"><?= me($m['sender_email']) ?></a></p>
<p><strong>Thời gian:</strong> <?= me($m['created_at']) ?> · <strong>Trạng thái:</strong> <?= (int) $m['is_read'] === 1 ? 'Đã đọc' : 'Chưa đọc' ?></p>
<div class="content"><?= me($m['content']) ?></div>
<form method="post" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= me(admin_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
<?php if ((int) $m['is_read'] === 0): ?><button name="action" value="read">Đánh dấu đã đọc</button><?php else: ?><button class="secondary" name="action" value="unread">Đánh dấu chưa đọc</button><?php endif; ?>
<button class="danger" name="action" value="delete" onclick="return confirm('Bạn có chắc muốn xóa tin nhắn này?')">Xóa tin nhắn</button>
</form></article><?php endforeach; ?>
</main></body></html>
