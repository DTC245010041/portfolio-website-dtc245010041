<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
admin_require_login();

$counts = [];
foreach ([
    'profile' => 'Hồ sơ cá nhân',
    'skills' => 'Kỹ năng',
    'projects' => 'Dự án',
    'messages' => 'Tin nhắn',
] as $table => $label) {
    $counts[$label] = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}
$unreadMessages = (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE is_read=0')->fetchColumn();
function de(mixed $v): string { return admin_escape((string) ($v ?? '')); }
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard | Portfolio</title>
<style>
*{box-sizing:border-box}body{margin:0;font:16px system-ui;background:#0b1120;color:#e5e7eb}header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;padding:20px 5%;background:#111827;border-bottom:1px solid #334155}main{max-width:1100px;margin:35px auto;padding:0 20px}.muted{color:#9ca3af}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin:25px 0}.card,.linkcard{background:#111827;border:1px solid #334155;border-radius:12px;padding:20px}.number{font-size:32px;font-weight:750;color:#7dd3fc}.links{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}.linkcard{display:block;color:#e5e7eb;text-decoration:none}.linkcard:hover{border-color:#38bdf8}.linkcard strong{display:block;margin-bottom:7px;color:#7dd3fc}a{color:#7dd3fc}button{padding:10px 14px;border-radius:8px;border:1px solid #7f1d1d;background:#451a1a;color:#fecaca;cursor:pointer}
</style></head>
<body>
<header>
<div><strong>Portfolio Admin</strong> <span class="muted">· Xin chào, <?= de($_SESSION['admin_username'] ?? '') ?></span></div>
<form method="post" action="logout.php">
<input type="hidden" name="csrf_token" value="<?= de(admin_csrf_token()) ?>">
<button type="submit">Đăng xuất</button>
</form>
</header>
<main>
<h1>Tổng quan quản trị</h1>
<p class="muted">Quản lý nội dung website Portfolio tại một nơi.</p>
<section class="grid">
<?php foreach ($counts as $label => $count): ?><article class="card">
<div class="muted"><?= de($label) ?></div><div class="number"><?= $count ?></div>
<?php if ($label === 'Tin nhắn'): ?><div class="muted">Chưa đọc: <?= $unreadMessages ?></div><?php endif; ?>
</article><?php endforeach; ?>
</section>
<h2>Chức năng quản trị</h2>
<section class="links">
<a class="linkcard" href="profile.php"><strong>Hồ sơ cá nhân</strong><span class="muted">Cập nhật thông tin và phần giới thiệu.</span></a>
<a class="linkcard" href="skills.php"><strong>Kỹ năng</strong><span class="muted">Thêm, sửa, xóa kỹ năng và mức thành thạo.</span></a>
<a class="linkcard" href="projects.php"><strong>Dự án</strong><span class="muted">Quản lý mô tả, công nghệ, đường dẫn và ảnh.</span></a>
<a class="linkcard" href="messages.php"><strong>Tin nhắn</strong><span class="muted">Xem liên hệ, đánh dấu đã đọc hoặc xóa.</span></a>
<a class="linkcard" href="../index.php"><strong>Xem website</strong><span class="muted">Mở trang Portfolio dành cho khách truy cập.</span></a>
</section>
</main></body></html>
