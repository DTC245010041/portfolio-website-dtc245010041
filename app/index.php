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
            color-scheme: dark;
            --bg: #080b18;
            --panel: rgba(18, 24, 48, .82);
            --panel-solid: #121830;
            --text: #f1f4ff;
            --muted: #a2acc8;
            --accent: #65e8ff;
            --accent2: #a78bfa;
            --border: rgba(130, 151, 210, .22);
            --glow: rgba(101, 232, 255, .18);
            --header: rgba(8, 11, 24, .82);
            --input-bg: #0c1225;
            --shadow: 0 18px 55px rgba(0, 0, 0, .24);
        }

        body[data-theme="light"] {
            color-scheme: light;
            --bg: #f5f7ff;
            --panel: rgba(255, 255, 255, .88);
            --panel-solid: #fff;
            --text: #171b35;
            --muted: #626b87;
            --accent: #087eae;
            --accent2: #7650d8;
            --border: rgba(83, 94, 150, .20);
            --glow: rgba(101, 91, 230, .12);
            --header: rgba(245, 247, 255, .88);
            --input-bg: #fff;
            --shadow: 0 18px 55px rgba(48, 57, 105, .10);
        }

        * { box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 95px;
        }

        body {
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            background:
                radial-gradient(ellipse at 12% 8%, var(--glow), transparent 34%),
                radial-gradient(ellipse at 88% 32%, rgba(167, 139, 250, .10), transparent 32%),
                var(--bg);
            color: var(--text);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            line-height: 1.75;
            transition: background .35s ease, color .35s ease;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            opacity: .17;
            background-image: radial-gradient(var(--accent) .65px, transparent .65px);
            background-size: 26px 26px;
            mask-image: linear-gradient(to bottom, black, transparent 75%);
        }

        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; }

        ::selection {
            color: #07111d;
            background: var(--accent);
        }

        .container {
            width: min(1120px, 90%);
            margin-inline: auto;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--header);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        nav {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
        }

        .logo {
            font-size: 21px;
            font-weight: 900;
            letter-spacing: -.8px;
            color: var(--accent);
            text-shadow: 0 0 24px var(--glow);
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        .nav-links a {
            position: relative;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
            transition: color .25s ease;
        }

        .nav-links a::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -7px;
            width: 0;
            height: 2px;
            border-radius: 2px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            transition: width .25s ease;
        }

        .nav-links a:hover { color: var(--accent); }
        .nav-links a:hover::after { width: 100%; }

        #theme-toggle {
            padding: 7px 12px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--panel);
            color: var(--text);
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background .25s ease, border-color .25s ease,
                        transform .25s ease;
        }

        #theme-toggle:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
        }

        .hero {
            position: relative;
            min-height: 570px;
            display: flex;
            align-items: center;
            padding-top: 88px;
            padding-bottom: 88px;
            animation: hero-enter .8s ease both;
        }

        .hero > div { max-width: 780px; }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--accent);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 26px;
            height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            box-shadow: 0 0 12px var(--glow);
        }

        h1 {
            margin: 18px 0;
            font-size: clamp(42px, 7vw, 78px);
            line-height: 1.1;
            letter-spacing: -2.5px;
            overflow-wrap: anywhere;
        }

        h1 span {
            color: var(--accent);
            background: linear-gradient(100deg, var(--accent), var(--accent2));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            max-width: 680px;
            color: var(--muted);
            font-size: clamp(16px, 2vw, 19px);
        }


        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: clamp(30px, 6vw, 80px);
        }

        .hero-copy {
            flex: 1;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .hero-photo-wrap {
            flex: 0 0 clamp(220px, 30vw, 340px);
            aspect-ratio: 1;
            padding: 5px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #8067ff, #ee82ff);
            box-shadow: 0 0 35px var(--glow), 0 0 85px rgba(125, 91, 255, .16);
            animation: portrait-float 5s ease-in-out infinite;
        }

        .hero-photo {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 35%;
            border: 4px solid var(--bg);
            border-radius: 50%;
        }

        @keyframes portrait-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        @media (max-width: 700px) {
            .hero {
                flex-direction: column-reverse;
                align-items: flex-start;
                gap: 28px;
            }

            .hero-photo-wrap {
                flex-basis: auto;
                width: min(64vw, 260px);
                align-self: center;
            }
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 18px;
            padding: 12px 22px;
            border: 1px solid transparent;
            border-radius: 12px;
            background: linear-gradient(110deg, var(--accent), #91c9ff 55%, #b8a1ff);
            color: #07111d;
            font-weight: 800;
            box-shadow: 0 8px 28px var(--glow);
            cursor: pointer;
            transition: transform .25s ease, box-shadow .25s ease, filter .25s ease;
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 13px 34px var(--glow);
            filter: saturate(1.12);
        }

        section {
            padding-top: 66px;
            padding-bottom: 66px;
            animation: section-enter .7s ease both;
        }


        /* Hieu ung xuat hien khi cuon trang */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition:
                opacity .7s ease,
                transform .7s cubic-bezier(.2, .7, .2, 1);
        }

        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: reduce) {
            .reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }

        .section-title {
            margin: 0 0 28px;
            font-size: clamp(26px, 4vw, 35px);
            font-weight: 850;
            letter-spacing: -.8px;
        }

        .section-title span {
            color: var(--accent);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .card,
        .contact-box {
            min-width: 0;
            padding: 25px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
            transition: transform .28s ease, border-color .28s ease, box-shadow .28s ease, background .35s ease;
        }

        .card:hover,
        .contact-box:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 18px 48px var(--glow);
        }

        .card h3 { margin-top: 0; line-height: 1.4; }
        .muted { color: var(--muted); }
        .contact-box p { overflow-wrap: anywhere; }

        .skill-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .skill-top strong { overflow-wrap: anywhere; }

        .progress {
            height: 8px;
            overflow: hidden;
            border-radius: 20px;
            background: var(--border);
        }

        .progress div {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            box-shadow: 0 0 16px var(--glow);
            animation: progress-enter 1.1s ease both;
        }

        .tag {
            display: inline-block;
            max-width: 100%;
            margin-top: 10px;
            padding: 5px 11px;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: var(--glow);
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        #contact a:not(.button) {
            color: var(--accent);
            overflow-wrap: anywhere;
        }

        #contact form { width: 100%; }

        #contact label {
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        #contact input,
        #contact textarea {
            width: 100%;
            font: inherit;
            color: var(--text);
            background: var(--input-bg) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        #contact input:focus,
        #contact textarea:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px var(--glow);
        }

        footer {
            margin-top: 45px;
            padding: 28px 0;
            border-top: 1px solid var(--border);
            color: var(--muted);
            text-align: center;
            font-size: 13px;
        }

        @keyframes hero-enter {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes section-enter {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes progress-enter {
            from { width: 0; }
        }

        @media (max-width: 850px) {
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            nav { padding: 15px 0; align-items: flex-start; }
            .nav-links { gap: 12px 17px; }
        }

        @media (max-width: 600px) {
            .container { width: min(100% - 32px, 1120px); }
            nav { flex-direction: column; gap: 10px; }
            .nav-links { gap: 10px 15px; }
            .nav-links a { font-size: 13px; }
            .hero { min-height: 420px; padding-top: 65px; padding-bottom: 55px; }
            h1 { font-size: clamp(39px, 12vw, 58px); letter-spacing: -1.5px; }
            section { padding-top: 44px; padding-bottom: 44px; }
            .grid { grid-template-columns: 1fr; }
            .card, .contact-box { padding: 21px; border-radius: 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }


        /* Dieu chinh menu tren man hinh nho */
        @media (max-width: 600px) {
            .nav-links {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                align-items: center;
                gap: 10px 14px;
                width: 100%;
            }

            .nav-links a {
                font-size: 13px;
            }

            #theme-toggle {
                min-height: 36px;
                padding: 7px 11px;
            }

            .logo {
                font-size: 19px;
            }

            .hero-copy {
                width: 100%;
            }
        }


        /* Header va hero theo mau portfolio */
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -.7px;
            white-space: nowrap;
        }
        .logo-code, .logo-dot { color: var(--accent); }
        .nav-cta {
            padding: 8px 14px;
            font-size: 13px;
            color: #080b18;
            font-weight: 800;
        }
        .hero {
            min-height: 570px;
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            align-items: center;
            gap: 50px;
            padding-top: 65px;
            padding-bottom: 70px;
        }
        .hero-copy { max-width: 650px; }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--accent);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
        }
        .status-dot, .hero-meta i {
            width: 8px;
            height: 8px;
            display: inline-block;
            border-radius: 50%;
            background: #42e6a4;
            box-shadow: 0 0 14px #42e6a4;
        }
        .hero h1 {
            margin: 18px 0;
            font-size: clamp(42px, 5.4vw, 70px);
            line-height: 1.12;
            letter-spacing: -2.5px;
            overflow-wrap: anywhere;
        }
        .hero h1 span {
            background: linear-gradient(100deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .hero-copy > p { max-width: 570px; color: var(--muted); font-size: 17px; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 13px; margin-top: 26px; }
        .button-outline { color: var(--text); background: transparent; border: 1px solid var(--border); }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 14px 22px; margin-top: 28px; color: var(--muted); font-size: 12px; }
        .hero-meta span:first-child { display: inline-flex; align-items: center; gap: 8px; }
        .hero-photo-wrap { position: relative; width: min(100%, 420px); margin-inline: auto; isolation: isolate; }
        .hero-photo-frame {
            position: relative;
            z-index: 1;
            overflow: hidden;
            aspect-ratio: 1;
            border: 1px solid rgba(101, 232, 255, .6);
            border-radius: 28px;
            background: var(--panel);
            box-shadow: 0 0 55px var(--glow), 0 25px 70px rgba(0, 0, 0, .25);
            transform: rotate(2deg);
        }
        .hero-photo { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center; }
        .hero-orbit { position: absolute; inset: -18px; border: 1px solid var(--border); border-radius: 34px; transform: rotate(-5deg); }
        .floating-chip {
            position: absolute;
            z-index: 2;
            padding: 10px 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--panel-solid);
            box-shadow: var(--shadow);
            font-size: 12px;
            font-weight: 700;
        }
        .floating-chip span { margin-right: 6px; color: var(--accent); }
        .chip-top { top: 12%; left: -35px; }
        .chip-bottom { right: -25px; bottom: 12%; }
        @media (max-width: 800px) {
            .hero { grid-template-columns: 1fr; gap: 45px; padding-top: 45px; }
            .hero-copy { order: 1; }
            .hero-photo-wrap { order: 0; width: min(72%, 340px); }
            .hero h1 { font-size: clamp(40px, 10vw, 60px); }
            .chip-top { left: -18px; }
            .chip-bottom { right: -18px; }
            .nav-cta { display: none; }
        }
        @media (max-width: 480px) {
            .hero-meta { flex-direction: column; }
            .hero-actions .button { width: 100%; text-align: center; }
            .floating-chip { font-size: 10px; padding: 8px 10px; }
        }


        /* Fix contrast for header contact button */
        header .nav-links a.nav-cta,
        header .nav-links a.nav-cta:link,
        header .nav-links a.nav-cta:visited,
        header .nav-links a.nav-cta:hover {
            color: #080b18 !important;
            -webkit-text-fill-color: #080b18;
            font-weight: 800 !important;
            text-shadow: none !important;
            opacity: 1 !important;
        }

/* Can chinh lai khu vuc Lien he */
#contact > .card {
    display: grid;
    grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
    gap: 28px;
    align-items: start;
    padding: 28px;
    max-width: 100%;
    margin-inline: auto;
}

#contact > .card > p:first-child {
    grid-column: 1 / -1;
    margin: 0;
}

#contact > .card > p:not(:first-child) {
    margin: 0;
}

#contact > .card > hr {
    grid-column: 1 / -1;
    width: 100%;
}

#contact form {
    width: 100%;
    max-width: none;
    min-width: 0;
}

#contact form input,
#contact form textarea,
#contact form select {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

#contact form button,
#contact form [type="submit"] {
    width: 100%;
}

@media (max-width: 700px) {
    #contact > .card {
        grid-template-columns: minmax(0, 1fr);
        padding: 20px;
        gap: 18px;
    }

    #contact > .card > p:first-child,
    #contact > .card > hr {
        grid-column: auto;
    }
}


/* Contact layout: balanced two-column design */
#contact > .contact-box {
    width: 100%;
    max-width: 1000px;
    box-sizing: border-box;
    margin: 0 auto;
    padding: 30px;
    display: grid;
    grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
    column-gap: 36px;
    align-items: start;
}

#contact > .contact-box > p:first-child {
    grid-column: 1 / -1;
    margin-top: 0;
    margin-bottom: 10px;
}

#contact > .contact-box > p:not(:first-child) {
    grid-column: 1;
    overflow-wrap: anywhere;
}

#contact > .contact-box > hr {
    display: none;
}

#contact > .contact-box > h3 {
    grid-column: 2;
    grid-row: 2;
    margin: 0 0 12px;
}

#contact > .contact-box > form {
    grid-column: 2;
    grid-row: 3 / span 5;
    width: 100%;
    max-width: none !important;
    min-width: 0;
    box-sizing: border-box;
}

#contact > .contact-box > form input:not([type="hidden"]),
#contact > .contact-box > form textarea,
#contact > .contact-box > form button {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

#contact > .contact-box > form input:not([type="hidden"]),
#contact > .contact-box > form textarea {
    min-width: 0;
}

#contact > .contact-box > p[role="status"] {
    grid-column: 2;
    grid-row: 8;
    overflow-wrap: anywhere;
}

@media (max-width: 700px) {
    #contact > .contact-box {
        grid-template-columns: minmax(0, 1fr);
        padding: 20px;
        gap: 12px;
    }

    #contact > .contact-box > p:first-child,
    #contact > .contact-box > p:not(:first-child),
    #contact > .contact-box > h3,
    #contact > .contact-box > form,
    #contact > .contact-box > p[role="status"] {
        grid-column: 1;
        grid-row: auto;
    }

    #contact > .contact-box > hr {
        display: block;
        grid-column: 1;
        margin: 12px 0 !important;
    }
}

</style>
</head>

<body>
<header>
    <nav class="container">
        <a class="logo" href="#home"><span class="logo-code">&lt;/&gt;</span> Đỗ Tuấn Hưng</a>
        <div class="nav-links">
            <a href="#home">Trang chủ</a>
            <a href="#about">Giới thiệu</a>
            <a href="#skills">Kỹ năng</a>
            <a href="#projects">Dự án</a>
            <a href="#contact">Liên hệ</a>
            <a class="button nav-cta" href="#contact">Liên hệ ngay ↗</a>
            <button type="button" id="theme-toggle"
                    aria-label="Chuyển giao diện sáng tối"
                    aria-pressed="false">☀ Sáng</button>
        </div>
    </nav>
</header>

<main>
    <section class="hero container" id="home">
        <div class="hero-copy">
            <div class="eyebrow"><span class="status-dot"></span> XIN CHÀO, TÔI LÀ</div>
            <h1><?= e($profile['full_name']) ?><br>
                <span><?= e($profile['job_title']) ?></span>
            </h1>
            <p><?= nl2br(e($profile['bio'])) ?></p>
            <div class="hero-actions">
                <a class="button" href="#projects">Khám phá dự án <span>↗</span></a>
                <a class="button button-outline" href="#contact">Liên hệ với tôi</a>
            </div>
            <div class="hero-meta">
                <span><i></i> Sẵn sàng học hỏi và hợp tác</span>
                <span>PHP · MySQL · Docker</span>
            </div>
        </div>

        <div class="hero-photo-wrap">
            <div class="hero-orbit"></div>
            <div class="hero-photo-frame">
                <img
                    class="hero-photo"
                    src="/assets/portrait.jpg"
                    alt="Ảnh chân dung Đỗ Tuấn Hưng"
                    width="420"
                    height="420"
                    fetchpriority="high"
                >
            </div>
            <div class="floating-chip chip-top"><span>✦</span> Web Developer</div>
            <div class="floating-chip chip-bottom"><span>⌘</span> Building for the web</div>
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

<script>
(() => {
    const button = document.getElementById('theme-toggle');
    if (!button) return;

    const applyTheme = (theme) => {
        document.body.dataset.theme = theme;
        const light = theme === 'light';
        button.textContent = light ? '☾ Tối' : '☀ Sáng';
        button.setAttribute('aria-pressed', String(light));
    };

    let savedTheme = 'dark';
    try {
        savedTheme = localStorage.getItem('portfolio-theme') || 'dark';
    } catch (_) {}

    applyTheme(savedTheme === 'light' ? 'light' : 'dark');

    button.addEventListener('click', () => {
        const next = document.body.dataset.theme === 'light' ? 'dark' : 'light';
        applyTheme(next);
        try {
            localStorage.setItem('portfolio-theme', next);
        } catch (_) {}
    });
})();
</script>


<script>
(() => {
    const targets = document.querySelectorAll(
        '#about, #skills, #projects, #contact, #about .card, #skills .card, #projects .card, #contact .contact-box'
    );

    if (!targets.length || !('IntersectionObserver' in window)) return;

    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    if (reducedMotion) return;

    targets.forEach((element, index) => {
        element.classList.add('reveal');
        element.style.transitionDelay = `${(index % 3) * 100}ms`;
    });

    const observer = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                currentObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -35px 0px'
    });

    targets.forEach(element => observer.observe(element));
})();
</script>

</body>
</html>
