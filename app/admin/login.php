<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, username, password_hash
             FROM admins
             WHERE username = :username
             LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            unset($_SESSION['admin_csrf']);

            header('Location: dashboard.php');
            exit;
        }
    }

    $error = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập Admin | Portfolio</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            padding: 20px; background: #0b1120; color: #e5e7eb;
            font-family: system-ui, sans-serif;
        }
        .panel {
            width: 100%; max-width: 410px; padding: 30px;
            background: #111827; border: 1px solid #263449;
            border-radius: 18px;
        }
        h1 { margin-top: 0; font-size: 27px; }
        p { color: #9ca3af; line-height: 1.6; }
        label { display: block; margin: 18px 0 7px; }
        input {
            width: 100%; padding: 12px; border-radius: 9px;
            border: 1px solid #374151; background: #0b1120;
            color: white; font-size: 16px;
        }
        button {
            width: 100%; margin-top: 22px; padding: 12px;
            border: 0; border-radius: 9px; background: #38bdf8;
            color: #082f49; font-weight: 700; font-size: 16px;
            cursor: pointer;
        }
        .error {
            padding: 11px; border-radius: 8px;
            background: #451a1a; color: #fecaca;
        }
        a { color: #7dd3fc; }
    </style>
</head>
<body>
<main class="panel">
    <h1>Quản trị Portfolio</h1>
    <p>Đăng nhập để quản lý nội dung website cá nhân.</p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= admin_escape($error) ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token"
               value="<?= admin_escape(admin_csrf_token()) ?>">

        <label for="username">Tên đăng nhập</label>
        <input id="username" name="username" type="text"
               maxlength="50" autocomplete="username" required>

        <label for="password">Mật khẩu</label>
        <input id="password" name="password" type="password"
               autocomplete="current-password" required>

        <button type="submit">Đăng nhập</button>
    </form>

    <p><a href="../index.php">← Quay lại website</a></p>
</main>
</body>
</html>
