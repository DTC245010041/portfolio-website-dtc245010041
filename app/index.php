<?php
declare(strict_types=1);

require __DIR__ . '/includes/db.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$profile = $pdo->query(
    'SELECT full_name, job_title, bio, email, phone, address
     FROM profile ORDER BY id ASC LIMIT 1'
)->fetch();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

$contactStatus = $_SESSION['contact_status'] ?? '';
unset($_SESSION['contact_status']);

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['form_action'] ?? '') === 'contact') {

    $token = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['contact_csrf'] ?? '');

    $name = trim((string) ($_POST['sender_name'] ?? ''));
    $email = trim((string) ($_POST['sender_email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));

    if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
        $_SESSION['contact_status'] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    } elseif (
        $name === '' || strlen($name) > 100
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 150
        || strlen($subject) > 200
        || $content === '' || strlen($content) > 10000
    ) {
        $_SESSION['contact_status'] = 'Thông tin chưa hợp lệ. Vui lòng kiểm tra lại các trường.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO messages
                    (sender_name, sender_email, subject, content)
                 VALUES
                    (:name, :email, :subject, :content)'
            );

            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'content' => $content,
            ]);

            $_SESSION['contact_status'] = 'Gửi tin nhắn thành công. Cảm ơn bạn đã liên hệ!';
            $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
        } catch (PDOException $e) {
            error_log('Portfolio contact form failed: ' . $e->getMessage());
            $_SESSION['contact_status'] = 'Chưa gửi được tin nhắn. Vui lòng thử lại sau.';
        }
    }

    header('Location: index.php#contact', true, 303);
    exit;
}if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

$contactStatus = $_SESSION['contact_status'] ?? '';
unset($_SESSION['contact_status']);

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['form_action'] ?? '') === 'contact') {

    $token = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['contact_csrf'] ?? '');

    $name = trim((string) ($_POST['sender_name'] ?? ''));
    $email = trim((string) ($_POST['sender_email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));

    if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
        $_SESSION['contact_status'] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    } elseif (
        $name === '' || strlen($name) > 100
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 150
        || strlen($subject) > 200
        || $content === '' || strlen($content) > 10000
    ) {
        $_SESSION['contact_status'] = 'Thông tin chưa hợp lệ. Vui lòng kiểm tra lại các trường.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO messages
                    (sender_name, sender_email, subject, content)
                 VALUES
                    (:name, :email, :subject, :content)'
            );

            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'content' => $content,
            ]);

            $_SESSION['contact_status'] = 'Gửi tin nhắn thành công. Cảm ơn bạn đã liên hệ!';
            $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
        } catch (PDOException $e) {
            error_log('Portfolio contact form failed: ' . $e->getMessage());
            $_SESSION['contact_status'] = 'Chưa gửi được tin nhắn. Vui lòng thử lại sau.';
        }
    }

    header('Location: index.php#contact', true, 303);
    exit;
}

$skills = $pdo->query(
    'SELECT name, level FROM skills ORDER BY level DESC, name ASC'
)->fetchAll();

$projects = $pdo->query(
    'SELECT title, description, tech_stack, project_url
     FROM projects ORDER BY id DESC'
)->fetchAll();

$profile = $profile ?: [
    'full_name' => 'Đỗ Tuấn Hưng',
    'job_title' => 'Web Developer',
    'bio' => 'Chào mừng bạn đến với website Portfolio của tôi.',
    'email' => 'DTC999999999@ictu.edu.vn',
    'phone' => '0123456789',
    'address' => '36 Rau má, Việt Nam',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portfolio cá nhân giới thiệu kỹ năng, dự án và thông tin liên hệ.">
    <title><?= e($profile['full_name']) ?> | Portfolio</title>

    <style>
        :root {
            --bg: #0b1120;
            --panel: #121c30;
            --text: #edf2ff;
            --muted: #9aa9c2;
            --accent: #68e1fd;
            --border: #26344c;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.7;
        }

        a { color: inherit; text-decoration: none; }

        .container {
            width: min(1080px, 90%);
            margin: auto;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(11, 17, 32, .94);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }

        nav {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            font-size: 22px;
            font-weight: 800;
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: var(--muted);
            font-size: 14px;
        }

        .nav-links a:hover { color: var(--accent); }

        .hero {
            min-height: 480px;
            display: flex;
            align-items: center;
            padding: 80px 0;
        }

        .eyebrow {
            color: var(--accent);
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        h1 {
            margin: 14px 0;
            font-size: clamp(38px, 7vw, 68px);
            line-height: 1.12;
            overflow-wrap: anywhere;
        }

        h1 span { color: var(--accent); }

        .hero p {
            max-width: 650px;
            color: var(--muted);
            font-size: 17px;
        }

        .button {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 22px;
            border-radius: 8px;
            background: var(--accent);
            color: #07111d;
            font-weight: bold;
        }

        section { padding: 60px 0; }

        .section-title {
            margin: 0 0 28px;
            font-size: 30px;
        }

        .section-title span { color: var(--accent); }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .card {
            padding: 24px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            min-width: 0;
        }

        .card h3 { margin-top: 0; }

        .muted { color: var(--muted); }

        .skill-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .progress {
            height: 7px;
            overflow: hidden;
            border-radius: 10px;
            background: #27354b;
        }

        .progress div {
            height: 100%;
            background: linear-gradient(90deg, #68e1fd, #8b9dff);
            border-radius: inherit;
        }

        .tag {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            background: #20344a;
            color: var(--accent);
            font-size: 12px;
        }

        .contact-box {
            padding: 30px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .contact-box p { overflow-wrap: anywhere; }

        footer {
            margin-top: 50px;
            padding: 25px 0;
            border-top: 1px solid var(--border);
            color: var(--muted);
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            nav { align-items: flex-start; padding: 18px 0; flex-direction: column; }
            .nav-links { gap: 14px; }
            .hero { min-height: 400px; padding: 55px 0; }
            section { padding: 42px 0; }
        }
    </style>
</head>

<body>
<header>
    <nav class="container">
        <a class="logo" href="#home">MY PORTFOLIO.</a>
        <div class="nav-links">
            <a href="#home">Trang chủ</a>
            <a href="#about">Giới thiệu</a>
            <a href="#skills">Kỹ năng</a>
            <a href="#projects">Dự án</a>
            <a href="#contact">Liên hệ</a>
        </div>
    </nav>
</header>

<main>
    <section class="hero container" id="home">
        <div>
            <div class="eyebrow">Xin chào, tôi là</div>
            <h1><?= e($profile['full_name']) ?><br>
                <span><?= e($profile['job_title']) ?></span>
            </h1>
            <p><?= nl2br(e($profile['bio'])) ?></p>
            <a class="button" href="#projects">Khám phá dự án &rarr;</a>
        </div>
    </section>

    <section class="container" id="about">
        <h2 class="section-title">01. <span>Giới thiệu</span></h2>
        <div class="card">
            <p><?= nl2br(e($profile['bio'])) ?></p>
            <?php if (!empty($profile['address'])): ?>
                <p class="muted">Địa điểm: <?= e($profile['address']) ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="container" id="skills">
        <h2 class="section-title">02. <span>Kỹ năng</span></h2>
        <div class="grid">
            <?php foreach ($skills as $skill): ?>
                <?php $level = max(0, min(100, (int) $skill['level'])); ?>
                <article class="card">
                    <div class="skill-top">
                        <strong><?= e($skill['name']) ?></strong>
                        <span class="muted"><?= $level ?>%</span>
                    </div>
                    <div class="progress">
                        <div style="width: <?= $level ?>%"></div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!$skills): ?>
                <p class="muted">Chưa có kỹ năng nào được cập nhật.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="container" id="projects">
        <h2 class="section-title">03. <span>Dự án tiêu biểu</span></h2>
        <div class="grid">
            <?php foreach ($projects as $project): ?>
                <article class="card">
                    <h3><?= e($project['title']) ?></h3>
                    <p class="muted"><?= nl2br(e($project['description'])) ?></p>
                    <span class="tag"><?= e($project['tech_stack']) ?></span>

                    <?php if (!empty($project['project_url'])): ?>
                        <p>
                            <a class="button"
                               href="<?= e($project['project_url']) ?>"
                               target="_blank"
                               rel="noopener noreferrer">Xem dự án &rarr;</a>
                        </p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>

            <?php if (!$projects): ?>
                <p class="muted">Chưa có dự án nào được cập nhật.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="container" id="contact">
        <h2 class="section-title">04. <span>Liên hệ</span></h2>
        <div class="contact-box">
            <p>Nếu bạn muốn trao đổi công việc hoặc hợp tác, hãy liên hệ với tôi.</p>

            <?php if (!empty($profile['email'])): ?>
                <p>Email: <a href="mailto:<?= e($profile['email']) ?>">
                    <?= e($profile['email']) ?></a></p>
            <?php endif; ?>

            <?php if (!empty($profile['phone'])): ?>
                <p>Điện thoại: <?= e($profile['phone']) ?></p>
            <?php endif; ?>

            <?php if (!empty($profile['address'])): ?>
                <p>Địa chỉ: <?= e($profile['address']) ?></p>
            <?php endif; ?>
<hr style="margin:28px 0;border:0;border-top:1px solid var(--border)">

<h3>Gửi tin nhắn cho tôi</h3>

<?php if ($contactStatus !== ''): ?>
    <p role="status"
       style="padding:12px;border-radius:8px;background:var(--panel);overflow-wrap:anywhere">
        <?= e($contactStatus) ?>
    </p>
<?php endif; ?>

<form method="post" action="#contact"
      style="display:grid;gap:14px;max-width:650px">

    <input type="hidden" name="form_action" value="contact">
    <input type="hidden" name="csrf_token"
           value="<?= e($_SESSION['contact_csrf']) ?>">

    <label for="sender_name">Họ và tên *</label>
    <input id="sender_name" name="sender_name" type="text"
           maxlength="100" autocomplete="name" required
           style="padding:12px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:var(--text)">

    <label for="sender_email">Email *</label>
    <input id="sender_email" name="sender_email" type="email"
           maxlength="150" autocomplete="email" required
           style="padding:12px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:var(--text)">

    <label for="subject">Tiêu đề</label>
    <input id="subject" name="subject" type="text"
           maxlength="200"
           style="padding:12px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:var(--text)">

    <label for="content">Nội dung tin nhắn *</label>
    <textarea id="content" name="content" rows="5"
              maxlength="10000" required
              style="padding:12px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:var(--text);resize:vertical"></textarea>

    <button type="submit" class="button">Gửi tin nhắn</button>
</form>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        &copy; <?= date('Y') ?> <?= e($profile['full_name']) ?>.
        Được xây dựng bằng PHP, MySQL và Docker.
    </div>
</footer>
</body>
</html>
