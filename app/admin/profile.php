<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
admin_require_login();

$stmt = $pdo->query('SELECT * FROM profile ORDER BY id ASC LIMIT 1');
$profile = $stmt->fetch();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();

    $data = [
        'full_name' => trim((string) ($_POST['full_name'] ?? '')),
        'job_title' => trim((string) ($_POST['job_title'] ?? '')),
        'bio'       => trim((string) ($_POST['bio'] ?? '')),
        'email'     => trim((string) ($_POST['email'] ?? '')),
        'phone'     => trim((string) ($_POST['phone'] ?? '')),
        'address'   => trim((string) ($_POST['address'] ?? '')),
    ];

    if ($data['full_name'] === '' || $data['email'] === ''
        || $data['phone'] === '' || $data['address'] === '') {
        $error = 'Họ tên, email, điện thoại và địa chỉ là bắt buộc.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ.';
    } elseif (mb_strlen($data['full_name']) > 100
        || mb_strlen($data['job_title']) > 150
        || mb_strlen($data['email']) > 150
        || mb_strlen($data['phone']) > 30
        || mb_strlen($data['address']) > 255) {
        $error = 'Một hoặc nhiều trường vượt quá độ dài cho phép.';
    } elseif (!$profile) {
        $error = 'Chưa có hồ sơ trong database. Hãy kiểm tra bảng profile trước.';
    } else {
        $update = $pdo->prepare(
            'UPDATE profile SET full_name=:full_name, job_title=:job_title,
             bio=:bio, email=:email, phone=:phone, address=:address
             WHERE id=:id'
        );
        $update->execute([
            ...$data,
            'id' => $profile['id'],
        ]);
        header('Location: profile.php?saved=1');
        exit;
    }

    $profile = array_merge($profile ?: [], $data);
}

function pe(mixed $value): string {
    return admin_escape((string) ($value ?? ''));
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hồ sơ cá nhân | Admin</title>
<style>
body{font:16px system-ui;background:#0b1120;color:#e5e7eb;margin:0;padding:24px}
main{max-width:760px;margin:auto;background:#111827;border:1px solid #334155;border-radius:14px;padding:24px}
label{display:block;margin-top:16px}input,textarea{width:100%;box-sizing:border-box;padding:11px;margin-top:6px;background:#0b1120;color:white;border:1px solid #475569;border-radius:7px}
textarea{min-height:130px}button,.link{display:inline-block;margin-top:18px;padding:11px 15px;border:0;border-radius:7px;background:#38bdf8;color:#082f49;font-weight:bold;text-decoration:none;cursor:pointer}
a{color:#7dd3fc}.error{color:#fecaca;background:#451a1a;padding:12px;border-radius:7px}.ok{color:#bbf7d0}
</style>
</head>
<body><main>
<h1>Hồ sơ cá nhân</h1>
<p><a href="dashboard.php">← Trang tổng quan</a></p>
<?php if ($error !== ''): ?><p class="error"><?= admin_escape($error) ?></p><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><p class="ok">Đã lưu hồ sơ.</p><?php endif; ?>
<?php if ($profile): ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= admin_escape(admin_csrf_token()) ?>">
<label>Họ và tên<input name="full_name" maxlength="100" required value="<?= pe($profile['full_name'] ?? '') ?>"></label>
<label>Chức danh<input name="job_title" maxlength="150" value="<?= pe($profile['job_title'] ?? '') ?>"></label>
<label>Giới thiệu<textarea name="bio"><?= pe($profile['bio'] ?? '') ?></textarea></label>
<label>Email<input type="email" name="email" maxlength="150" required value="<?= pe($profile['email'] ?? '') ?>"></label>
<label>Điện thoại<input name="phone" maxlength="30" required value="<?= pe($profile['phone'] ?? '') ?>"></label>
<label>Địa chỉ<input name="address" maxlength="255" required value="<?= pe($profile['address'] ?? '') ?>"></label>
<button type="submit">Lưu hồ sơ</button>
</form>
<?php else: ?>
<p>Database chưa có hồ sơ để chỉnh sửa. Không có dữ liệu nào bị thay đổi.</p>
<?php endif; ?>
</main></body></html>
