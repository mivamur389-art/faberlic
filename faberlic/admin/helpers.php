<?php
declare(strict_types=1);

/** Экранирование для HTML-вывода (XSS) */
function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Получить/создать CSRF-токен */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Проверить CSRF-токен из POST/GET */
function csrf_check(?string $token): void
{
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('CSRF token mismatch. Обновите страницу и попробуйте снова.');
    }
}

/** Установить flash-сообщение */
function flash_set(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

/** Забрать flash-сообщение (и очистить) */
function flash_get(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** Редирект с сохранением flash */
function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}