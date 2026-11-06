<?php
// Этот файл нужен для обхода блокировок браузера
// Он принимает данные и возвращает HTML-скрипт, который покажет сообщение на главной странице

// Тот же код, что и в send.php, но в конце он возвращает скрипт для родительского окна
$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$consent = isset($_POST['consent']);

if (mb_strlen($name) < 2 || !$consent) {
    echo "<script>window.parent.postMessage({status:'error', message:'Ошибка: проверьте имя и галочку'}, '*');</script>";
    exit;
}

$dbHost = 'localhost';
$dbName = 'site_db'; 
$dbUser = 'root';    
$dbPass = '';        

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $stmt = $pdo->prepare("INSERT INTO leads (name, phone, email, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $email, $message]);
    
    // Если успех, отправляем сообщение на главную страницу
    echo "<script>window.parent.postMessage({status:'success', message:'Спасибо! Заявка отправлена.'}, '*');</script>";
} catch (PDOException $e) {
    echo "<script>window.parent.postMessage({status:'error', message:'Ошибка БД: " . addslashes($e->getMessage()) . "'}, '*');</script>";
}
?>