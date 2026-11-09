<?php
declare(strict_types=1);

// ---------- Конфигурация ----------
const DB_HOST = 'localhost';
const DB_NAME = 'site_db';
const DB_USER = 'root';
const DB_PASS = '';

// ---------- Приём данных ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$name    = trim((string)($_POST['name']    ?? ''));
$phone   = trim((string)($_POST['phone']   ?? ''));
$email   = trim((string)($_POST['email']   ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$consent = isset($_POST['consent']) && $_POST['consent'] !== '';

// ---------- Валидация ----------
$errors = [];

if (mb_strlen($name) < 2) {
    $errors[] = 'name';
}
if (!preg_match('/^[+\d][\d\s\-\(\)]{6,}$/', $phone)) {
    $errors[] = 'phone';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email';
}
if (!$consent) {
    $errors[] = 'consent';
}

if ($errors) {
    header('Location: index.html?error=' . urlencode(implode(',', $errors)) . '#form');
    exit;
}

// ---------- Сохранение ----------
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO leads (name, phone, email, message, status, created_at)
         VALUES (?, ?, ?, ?, "new", NOW())'
    );
    $stmt->execute([$name, $phone, $email, $message]);

    header('Location: index.html?sent=1#form');
    exit;

} catch (PDOException $e) {
    // В проде — лог в файл, не вывод в браузер
    error_log('send.php DB error: ' . $e->getMessage());
    header('Location: index.html?error=server#form');
    exit;
}