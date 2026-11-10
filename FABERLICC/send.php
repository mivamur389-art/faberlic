<?php
// Включаем показ всех ошибок
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h2>Тест отправки формы</h2>";

// 1. Проверяем, пришли ли данные
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>Данные получены: Имя = " . ($_POST['name'] ?? 'нет') . "</p>";
} else {
    echo "<p style='color:red'>Ошибка: данные не отправлены (метод не POST).</p>";
}

// 2. Проверяем подключение к базе
$dbHost = 'localhost';
$dbName = 'site_db'; 
$dbUser = 'root';    
$dbPass = '';        

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>Подключение к БД 'site_db' успешно!</p>";

    // 3. Пробуем вставить данные
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $message = $_POST['message'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO leads (name, phone, email, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $message]);
        echo "<p style='color:green; font-weight:bold;'>УСПЕХ: Заявка добавлена в базу!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>ОШИБКА БАЗЫ ДАННЫХ: " . $e->getMessage() . "</p>";
}
?>