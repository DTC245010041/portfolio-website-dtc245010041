<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
admin_require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim((string) ($_POST['name'] ?? ''));
    $levelRaw = (string) ($_POST['level'] ?? '');
    $level = filter_var($levelRaw, FILTER_VALIDATE_INT);

    if ($action === 'delete') {
        if (!$id || $id < 1) {
            $error = 'ID kỹ năng không hợp lệ.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM skills WHERE id=:id');
            $stmt->execute(['id' => $id]);
            header('Location: skills.php?done=deleted');
            exit;
        }
    } elseif (in_array($action, ['create', 'update'], true)) {
        if ($name === '' || mb_strlen($name) > 100) {
            $error = 'Tên kỹ năng không được để trống và tối đa 100 ký tự.';
        } elseif ($level === false || $level < 0 || $level > 100) {
            $error = 'Mức độ phải là số nguyên từ 0 đến 100.';
        } elseif ($action === 'update' && (!$id || $id < 1)) {
            $error = 'ID kỹ năng không hợp lệ.';
        } else {
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO skills (name, level) VALUES (:name, :level)');
                $stmt->execute(['name' => $name, 'level' => $level]);
            } else {
                $stmt = $pdo->prepare('UPDATE skills SET name=:name, level=:level WHERE id=:id');
                $stmt->execute(['name' => $name, 'level' => $level, 'id' => $id]);
            }
            header('Location: skills.php?done=saved');
            exit;
        }
    } else {
        $error = 'Thao tác không hợp lệ.';
    }
}

$edit = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, level FROM skills WHERE id=:id');
    $stmt->execute(['id' => $editId]);
    $edit = $stmt->fetch() ?: null;
}
$skills = $pdo->query('SELECT id, name, level FROM skills ORDER BY level DESC, name ASC')->fetchAll();
function se(mixed $v): string { return admin_escape((string) ($v ?? '')); }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quản lý kỹ năng</title>
<style>
body{font:16px system-ui;background:#0b1120;color:#e5e7eb;margin:0;padding:24px}main{max-width:1000px;margin:auto}section,.item{background:#111827;border:1px solid #334155;border-radius:12px;padding:18px;margin:14px 0}input{box-sizing:border-box;padding:10px;background:#0b1120;color:white;border:1px solid #475569;border-radius:6px;max-width:100%;width:100%;margin:5px 0 12px}label{display:block}button,.btn{padding:9px 12px;border:0;border-radius:6px;background:#38bdf8;color:#082f49;cursor:pointer;text-decoration:none;display:inline-block;margin:3px}a{color:#7dd3fc}.danger{background:#7f1d1d;color:#fee2e2}.row{display:grid;grid-template-columns:2fr 1fr;gap:12px}.error{color:#fecaca}
@media(max-width:600px){.row{grid-template-columns:1fr}}
</style></head><body><main>
<h1>Quản lý kỹ năng</h1><p><a href="dashboard.php">← Trang tổng quan</a></p>
<?php if ($error): ?><p class="error"><?= se($error) ?></p><?php endif; ?>
<?php if (isset($_GET['done'])): ?><p>Đã xử lý thao tác thành công.</p><?php endif; ?>
<section><h2><?= $edit ? 'Sửa kỹ năng' : 'Thêm kỹ năng' ?></h2>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= se(admin_csrf_token()) ?>">
<input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
<?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
<div class="row"><label>Tên kỹ năng<input name="name" maxlength="100" required value="<?= se($edit['name'] ?? '') ?>"></label>
<label>Mức độ (0–100)<input type="number" name="level" min="0" max="100" required value="<?= se($edit['level'] ?? 80) ?>"></label></div>
<button type="submit"><?= $edit ? 'Lưu thay đổi' : 'Thêm kỹ năng' ?></button>
<?php if ($edit): ?><a class="btn" href="skills.php">Hủy sửa</a><?php endif; ?>
</form></section>
<h2>Danh sách kỹ năng (<?= count($skills) ?>)</h2>
<?php foreach ($skills as $s): ?><section class="item">
<strong><?= se($s['name']) ?></strong> — <?= (int) $s['level'] ?>%
<div><a class="btn" href="skills.php?edit=<?= (int) $s['id'] ?>">Sửa</a>
<form method="post" style="display:inline" onsubmit="return confirm('Bạn có chắc muốn xóa kỹ năng này?')">
<input type="hidden" name="csrf_token" value="<?= se(admin_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="danger" type="submit">Xóa</button>
</form></div></section><?php endforeach; ?>
</main></body></html>
