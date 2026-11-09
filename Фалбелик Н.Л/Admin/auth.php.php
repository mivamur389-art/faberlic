<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

/**
 * Подключение к БД. Бросает RuntimeException при ошибке.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        $details = APP_DEBUG ? $e->getMessage() : 'детали скрыты';
        http_response_code(500);
        exit('Ошибка подключения к БД. Проверьте, что MySQL запущен и база site_db создана. ' . e($details));
    }

    return $pdo;
}

/**
 * Старт сессии с безопасными cookie.
 * Должен вызываться до любого вывода.
 */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // раскомментировать при HTTPS
    ]);
    session_name('ADMIN_SID');
    session_start();

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!isset($_SESSION['ip'])) {
        $_SESSION['ip'] = $ip;
    } elseif ($_SESSION['ip'] !== $ip) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['ip'] = $ip;
    }
}

/** Текущий авторизованный админ или null */
function current_admin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

/** Требовать авторизацию (иначе → login.php) */
function require_auth(): void
{
    if (!current_admin()) {
        redirect('login.php');
    }
}

/**
 * Попытка входа.
 * Возвращает true при успехе, иначе false.
 * Защита от брутфорса: sleep + счётчик попыток в сессии + блокировка.
 */
function attempt_login(string $login, string $password): bool
{
    session_boot();

    $lockUntil = (int)($_SESSION['login_lock_until'] ?? 0);
    if ($lockUntil > time()) {
        flash_set('error', 'Слишком много попыток. Попробуйте через ' . ($lockUntil - time()) . ' сек.');
        return false;
    }

    $stmt = db()->prepare('SELECT id, login, password_hash FROM users WHERE login = ? LIMIT 1');
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    // ✅ Обход пароля УДАЛЁН — только password_verify
    $ok = $user && password_verify($password, $user['password_hash']);

    if (!$ok) {
        sleep(LOGIN_SLEEP_SECONDS);
        $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;

        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['login_lock_until'] = time() + LOGIN_LOCK_SECONDS;
            $_SESSION['login_attempts']   = 0;
            flash_set('error', 'Аккаунт временно заблокирован на ' . LOGIN_LOCK_SECONDS . ' сек.');
        } else {
            $left = MAX_LOGIN_ATTEMPTS - (int)$_SESSION['login_attempts'];
            flash_set('error', 'Неверный логин или пароль. Осталось попыток: ' . $left . '.');
        }
        return false;
    }

    unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
    session_regenerate_id(true);

    $_SESSION['admin'] = [
        'id'    => (int)$user['id'],
        'login' => $user['login'],
        'ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    return true;
}

/** Выход */
function logout(): never
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    redirect('login.php');
}