<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
session_boot();
require_auth();

$pdo = db();

$q      = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE;

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if (isset(STATUSES[$status])) {
    $where[] = 'status = ?';
    $params[] = $status;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$cntStmt = $pdo->prepare('SELECT COUNT(*) FROM leads' . $whereSql);
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();
$pages = max(1, (int)ceil($total / PER_PAGE));

if ($page > $pages && $total > 0) {
    redirect('index.php?' . http_build_query(array_filter([
        'q' => $q, 'status' => $status, 'page' => $pages,
    ])));
}

$sql = 'SELECT id, name, phone, email, message, status, created_at
        FROM leads' . $whereSql . '
        ORDER BY created_at DESC, id DESC
        LIMIT ' . PER_PAGE . ' OFFSET ' . $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

function url_with(array $overrides = []): string
{
    $base = array_filter([
        'q'      => $_GET['q']      ?? '',
        'status' => $_GET['status'] ?? '',
        'page'   => $_GET['page']   ?? '',
    ], fn($v) => $v !== '' && $v !== null);

    $params = array_merge($base, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

$flash = flash_get();
$csrf  = csrf_token();
$admin = current_admin();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки — Faberlic Console</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-page">

<header class="topbar">
    <div class="container topbar-inner">
        <a class="brand" href="index.php">Faberlic · Admin</a>
        <div class="topbar-right">
            <span class="muted small">Вы: <strong><?= e($admin['login']) ?></strong></span>
            <form method="post" action="actions.php" style="display:inline">
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <button class="btn btn-ghost btn-small" type="submit">Выйти</button>
            </form>
        </div>
    </div>
</header>

<main class="container">

    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['text']) ?></div>
    <?php endif; ?>

    <section class="toolbar">
        <form method="get" action="index.php" class="toolbar-form">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Поиск: имя, телефон, e-mail" class="input-search">
            <select name="status" class="select-status">
                <option value="">Все статусы</option>
                <?php foreach (STATUSES as $code => $label): ?>
                    <option value="<?= e($code) ?>" <?= $status === $code ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Найти</button>
            <?php if ($q !== '' || $status !== ''): ?>
                <a href="index.php" class="btn btn-ghost">Сбросить</a>
            <?php endif; ?>
        </form>

        <a class="btn btn-outline" href="actions.php?<?= e(http_build_query(array_filter([
            'action' => 'export', 'q' => $q, 'status' => $status,
        ]))) ?>">Скачать CSV</a>
    </section>

    <p class="muted small">Всего заявок: <strong><?= $total ?></strong>
        <?php if ($q !== '' || $status !== ''): ?> · фильтр активен<?php endif; ?>
    </p>

    <?php if (empty($leads)): ?>
        <div class="empty-state">Пока нет заявок по заданным условиям.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="leads-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Дата</th>
                <th>Имя</th>
                <th>Телефон</th>
                <th>E-mail</th>
                <th>Сообщение</th>
                <th>Статус</th>
                <th class="actions-col">Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($leads as $lead):
                $statusCode  = $lead['status'];
                $statusLabel = STATUSES[$statusCode] ?? $statusCode;
                $isNew       = $statusCode === 'new';
                ?>
                <tr class="<?= $isNew ? 'row-new' : '' ?>">
                    <td><?= (int)$lead['id'] ?></td>
                    <td class="nowrap"><?= e(date('d.m.Y H:i', strtotime($lead['created_at']))) ?></td>
                    <td>
                        <?= e($lead['name']) ?>
                        <?php if ($isNew): ?><span class="badge badge-new">new</span><?php endif; ?>
                    </td>
                    <td class="nowrap"><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $lead['phone'])) ?>"><?= e($lead['phone']) ?></a></td>
                    <td>
                        <?php if ($lead['email'] !== ''): ?>
                            <a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="msg-cell">
                        <?php if ($lead['message'] !== ''): ?>
                            <button type="button" class="link-like js-view-msg"
                                    data-id="<?= (int)$lead['id'] ?>"
                                    data-name="<?= e($lead['name']) ?>"
                                    data-msg="<?= e($lead['message']) ?>">
                                Показать
                            </button>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="actions.php" class="status-form">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
                            <input type="hidden" name="back_q" value="<?= e($q) ?>">
                            <input type="hidden" name="back_status" value="<?= e($status) ?>">
                            <input type="hidden" name="back_page" value="<?= (int)$page ?>">
                            <select name="status" onchange="this.form.submit()" class="status-select status-<?= e($statusCode) ?>">
                                <?php foreach (STATUSES as $code => $label): ?>
                                    <option value="<?= e($code) ?>" <?= $statusCode === $code ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="actions-col">
                        <form method="post" action="actions.php" onsubmit="return confirm('Удалить заявку #<?= (int)$lead['id'] ?>? Действие необратимо.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Пагинация">
            <?php if ($page > 1): ?>
                <a class="page-link" href="<?= e(url_with(['page' => $page - 1])) ?>">← Назад</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end   = min($pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
                ?>
                <a class="page-link <?= $i === $page ? 'active' : '' ?>" href="<?= e(url_with(['page' => $i])) ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
                <a class="page-link" href="<?= e(url_with(['page' => $page + 1])) ?>">Вперёд →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
    <?php endif; ?>

</main>

<div class="modal-overlay" id="msgModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="msgTitle">
        <button class="modal-close" type="button" aria-label="Закрыть" data-close>&times;</button>
        <h2 id="msgTitle">Сообщение</h2>
        <p class="muted small" id="msgAuthor"></p>
        <div class="msg-full" id="msgText"></div>
    </div>
</div>

<script>
(function () {
    'use strict';
    const modal = document.getElementById('msgModal');
    if (!modal) return;

    const author = document.getElementById('msgAuthor');
    const text   = document.getElementById('msgText');

    function open() {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.js-view-msg').forEach(function (btn) {
        btn.addEventListener('click', function () {
            author.textContent = 'От: ' + (btn.dataset.name || '');
            text.textContent = btn.dataset.msg || '';
            open();
        });
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) close();
    });
    modal.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });
})();
</script>

</body>
</html>