<?php
// admin.php — простая читалка leads.txt без БД
$file_path = __DIR__ . '/leads.txt';

if (!file_exists($file_path)) {
    file_put_contents($file_path, '');
}

$leads = [];
$lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines) {
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 5) {
            $leads[] = [
                'date'    => $parts[0],
                'name'    => $parts[1],
                'phone'   => $parts[2],
                'email'   => $parts[3],
                'message' => $parts[4]
            ];
        }
    }
}
$leads = array_reverse($leads);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - Заявки Faberlic</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 20px; color: #1f2430; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 14px; box-shadow: 0 8px 28px rgba(0,0,0,0.08); }
        h1 { color: #c2185b; margin-top: 0; }
        .stats { display: inline-block; background: #fdf2f6; color: #c2185b; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eeeeee; vertical-align: top; }
        th { background: #fdf2f6; color: #c2185b; font-weight: 600; }
        tr:hover { background: #fafafa; }
        .empty { text-align: center; color: #5b6472; padding: 40px; background: #f9f9f9; border-radius: 10px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; background: #e3f2fd; color: #1565c0; }
        .btn-refresh { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #c2185b; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; }
        .btn-refresh:hover { background: #8e0f42; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Заявки с сайта</h1>
        <div class="stats">Всего заявок: <?= count($leads) ?></div>

        <?php if (count($leads) > 0): ?>
            <table>
                <thead>
                    <tr><th>Дата</th><th>Имя</th><th>Телефон</th><th>Email</th><th>Комментарий</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><span class="badge"><?= htmlspecialchars($lead['date']) ?></span></td>
                            <td><strong><?= htmlspecialchars($lead['name']) ?></strong></td>
                            <td><?= htmlspecialchars($lead['phone']) ?></td>
                            <td><?= htmlspecialchars($lead['email'] ?: '-') ?></td>
                            <td><?= nl2br(htmlspecialchars($lead['message'] ?: '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">
                <p>Пока нет ни одной заявки. Отправьте тестовую через форму на сайте.</p>
            </div>
        <?php endif; ?>

        <a href="admin.php" class="btn-refresh">Обновить список</a>
    </div>
</body>
</html>