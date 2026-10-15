<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

admin_require_login();

if (!function_exists('pre')) {
    function pre(mixed $value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}

$error = '';
$success = '';
$edit = null;

// Xử lý thêm, sửa, xóa dự án
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create' || $action === 'update') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $techStack = trim($_POST['tech_stack'] ?? '');

            // Không lưu NULL vào các cột URL
            $projectUrl = trim($_POST['project_url'] ?? '');
            $imageUrl = trim($_POST['image_url'] ?? '');

            if ($title === '') {
                throw new RuntimeException('Vui lòng nhập tiêu đề dự án.');
            }

            if (mb_strlen($title) > 200) {
                throw new RuntimeException('Tiêu đề không được vượt quá 200 ký tự.');
            }

            if (mb_strlen($techStack) > 255) {
                throw new RuntimeException('Công nghệ sử dụng không được vượt quá 255 ký tự.');
            }

            if (mb_strlen($projectUrl) > 500 || mb_strlen($imageUrl) > 500) {
                throw new RuntimeException('URL không được vượt quá 500 ký tự.');
            }

            // Chỉ chấp nhận URL HTTP/HTTPS hoặc để trống
            foreach ([
                'URL dự án' => $projectUrl,
                'URL ảnh minh họa' => $imageUrl,
            ] as $label => $url) {
                if (
                    $url !== ''
                    && (
                        !filter_var($url, FILTER_VALIDATE_URL)
                        || !in_array(
                            strtolower((string) parse_url($url, PHP_URL_SCHEME)),
                            ['http', 'https'],
                            true
                        )
                    )
                ) {
                    throw new RuntimeException($label . ' phải là địa chỉ HTTP/HTTPS hợp lệ.');
                }
            }

            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO projects
                        (title, description, tech_stack, project_url, image_url)
                     VALUES
                        (:title, :description, :tech_stack, :project_url, :image_url)'
                );

                $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'tech_stack' => $techStack,
                    'project_url' => $projectUrl,
                    'image_url' => $imageUrl,
                ]);

                header('Location: projects.php?success=created');
                exit;
            }

            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new RuntimeException('ID dự án không hợp lệ.');
            }

            $stmt = $pdo->prepare(
                'UPDATE projects
                 SET title = :title,
                     description = :description,
                     tech_stack = :tech_stack,
                     project_url = :project_url,
                     image_url = :image_url
                 WHERE id = :id'
            );

            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'tech_stack' => $techStack,
                'project_url' => $projectUrl,
                'image_url' => $imageUrl,
                'id' => $id,
            ]);

            header('Location: projects.php?success=updated');
            exit;
        }

        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new RuntimeException('ID dự án không hợp lệ.');
            }

            $stmt = $pdo->prepare('DELETE FROM projects WHERE id = :id');
            $stmt->execute(['id' => $id]);

            header('Location: projects.php?success=deleted');
            exit;
        }

        throw new RuntimeException('Thao tác không hợp lệ.');
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        error_log('Portfolio projects error: ' . $e->getMessage());
        $error = 'Không thể lưu dữ liệu dự án. Vui lòng kiểm tra dữ liệu và thử lại.';
    }
}

if (isset($_GET['success'])) {
    $success = match ($_GET['success']) {
        'created' => 'Đã thêm dự án thành công.',
        'updated' => 'Đã cập nhật dự án thành công.',
        'deleted' => 'Đã xóa dự án thành công.',
        default => '',
    };
}

// Lấy dự án cần sửa
if (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

    if ($editId && $editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
        $stmt->execute(['id' => $editId]);
        $edit = $stmt->fetch() ?: null;
    }

    if (!$edit && $error === '') {
        $error = 'Không tìm thấy dự án cần sửa.';
    }
}

// Lấy danh sách dự án
$stmt = $pdo->query('SELECT * FROM projects ORDER BY id DESC');
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý dự án</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px;
            background: #f3f4f6;
            color: #1f2937;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 1000px;
            margin: 0 auto;
        }

        h1, h2 { margin-top: 0; }

        section {
            background: #fff;
            padding: 22px;
            margin-bottom: 22px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        label {
            display: block;
            margin-bottom: 16px;
            font-weight: 600;
        }

        input, textarea {
            display: block;
            width: 100%;
            margin-top: 7px;
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        button, .btn {
            display: inline-block;
            padding: 10px 15px;
            border: 0;
            border-radius: 7px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font: inherit;
            cursor: pointer;
        }

        .btn-secondary { background: #4b5563; }
        .btn-danger { background: #dc2626; }

        .item {
            overflow-wrap: anywhere;
        }

        .muted { color: #6b7280; }

        .message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 7px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .actions form { margin: 0; }

        @media (max-width: 600px) {
            body { padding: 12px; }
            .grid { grid-template-columns: 1fr; gap: 0; }
            section { padding: 16px; }
        }
    </style>
</head>
<body>
<main>
    <h1>Quản lý dự án</h1>

    <p>
        <a class="btn btn-secondary" href="dashboard.php">← Về trang quản trị</a>
        <a class="btn btn-secondary" href="../index.php" target="_blank" rel="noopener noreferrer">
            Xem website
        </a>
    </p>

    <?php if ($success !== ''): ?>
        <div class="message success"><?= pre($success) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="message error"><?= pre($error) ?></div>
    <?php endif; ?>

    <section>
        <h2><?= $edit ? 'Sửa dự án' : 'Thêm dự án mới' ?></h2>

        <form method="post">
            <input type="hidden" name="csrf_token"
                   value="<?= pre(admin_csrf_token()) ?>">

            <input type="hidden" name="action"
                   value="<?= $edit ? 'update' : 'create' ?>">

            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
            <?php endif; ?>

            <label>
                Tiêu đề *
                <input name="title" maxlength="200" required
                       value="<?= pre($edit['title'] ?? '') ?>">
            </label>

            <label>
                Mô tả
                <textarea name="description"><?= pre($edit['description'] ?? '') ?></textarea>
            </label>

            <div class="grid">
                <label>
                    Công nghệ sử dụng
                    <input name="tech_stack" maxlength="255"
                           placeholder="PHP, MySQL, Docker"
                           value="<?= pre($edit['tech_stack'] ?? '') ?>">
                </label>

                <label>
                    URL dự án
                    <input type="url" name="project_url" maxlength="500"
                           placeholder="https://example.com"
                           value="<?= pre($edit['project_url'] ?? '') ?>">
                </label>
            </div>

            <label>
                URL ảnh minh họa
                <input type="url" name="image_url" maxlength="500"
                       placeholder="https://example.com/image.jpg"
                       value="<?= pre($edit['image_url'] ?? '') ?>">
            </label>

            <button type="submit">
                <?= $edit ? 'Lưu thay đổi' : 'Thêm dự án' ?>
            </button>

            <?php if ($edit): ?>
                <a class="btn btn-secondary" href="projects.php">Hủy sửa</a>
            <?php endif; ?>
        </form>
    </section>

    <h2>Danh sách dự án (<?= count($projects) ?>)</h2>

    <?php foreach ($projects as $p): ?>
        <section class="item">
            <h3><?= pre($p['title']) ?></h3>

            <p><?= nl2br(pre($p['description'] ?? '')) ?></p>

            <p>
                <strong>Công nghệ:</strong>
                <?= pre($p['tech_stack'] ?? '') ?>
            </p>

            <?php if (!empty($p['project_url'])): ?>
                <p>
                    <a href="<?= pre($p['project_url']) ?>"
                       target="_blank" rel="noopener noreferrer">
                        Mở dự án
                    </a>
                </p>
            <?php endif; ?>

            <?php if (!empty($p['image_url'])): ?>
                <p>
                    <a href="<?= pre($p['image_url']) ?>"
                       target="_blank" rel="noopener noreferrer">
                        Xem ảnh minh họa
                    </a>
                </p>
            <?php endif; ?>

            <div class="actions">
                <a class="btn" href="projects.php?edit=<?= (int) $p['id'] ?>">
                    Sửa
                </a>

                <form method="post"
                      onsubmit="return confirm('Bạn có chắc muốn xóa dự án này?')">
                    <input type="hidden" name="csrf_token"
                           value="<?= pre(admin_csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">

                    <button type="submit" class="btn-danger">Xóa</button>
                </form>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if (!$projects): ?>
        <section>
            <p class="muted">Chưa có dự án nào. Hãy thêm dự án đầu tiên.</p>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
