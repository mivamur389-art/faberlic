<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
session_boot();

if (current_admin()) {
    redirect('index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $login    = trim((string)($_POST['login'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = 'Введите логин и пароль.';
    } elseif (attempt_login($login, $password)) {
        redirect('index.php');
    }
}

$flash = flash_get();
$csrf  = csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>Админ-панель</h1>
        <p class="muted">Управление заявками с сайта</p>

        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['text']) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

            <label for="login">Логин</label>
            <input id="login" name="login" type="text" required autofocus>

            <label for="password">Пароль</label>
            <input id="password" name="password" type="password" required>

            <button type="submit" class="btn btn-primary">Войти</button>
        </form>

        <p class="muted small">Защищено: CSRF, антибрутфорс, сессии.</p>
    </div>
</body>
</html>