<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

function read_hidden(string $prompt): string
{
    fwrite(STDOUT, $prompt);

    $hidden = function_exists('shell_exec')
        ? shell_exec('stty -echo 2>/dev/null')
        : null;

    try {
        $value = fgets(STDIN);
    } finally {
        if ($hidden !== null) {
            shell_exec('stty echo 2>/dev/null');
            fwrite(STDOUT, PHP_EOL);
        }
    }

    return trim($value === false ? '' : $value);
}

$username = '';
fwrite(STDOUT, "Tên tài khoản Admin: ");
$username = trim((string) fgets(STDIN));

if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Tên tài khoản phải có 3-50 ký tự: chữ, số, dấu chấm, gạch ngang hoặc gạch dưới.\n");
    exit(1);
}

$password = read_hidden("Mật khẩu Admin (ít nhất 12 ký tự): ");

if (strlen($password) < 12) {
    fwrite(STDERR, "Mật khẩu phải có ít nhất 12 ký tự.\n");
    exit(1);
}

$confirm = read_hidden("Nhập lại mật khẩu: ");

if (!hash_equals($password, $confirm)) {
    fwrite(STDERR, "Hai mật khẩu không khớp.\n");
    exit(1);
}

$stmt = $pdo->prepare(
    'INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)'
);

try {
    $stmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Đã tạo tài khoản Admin thành công.\n");
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        fwrite(STDERR, "Tên tài khoản đã tồn tại. Hãy chọn tên khác.\n");
        exit(1);
    }

    throw $e;
}
