<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
session_boot();
require_auth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit('Method Not Allowed');
        }
        csrf_check($_POST['csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('DELETE FROM leads WHERE id = ?');
            $stmt->execute([$id]);
            flash_set('success', 'Заявка #' . $id . ' удалена.');
        }
        redirect('index.php');

    case 'status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit('Method Not Allowed');
        }
        csrf_check($_POST['csrf'] ?? null);

        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($id > 0 && isset(STATUSES[$status])) {
            $stmt = db()->prepare('UPDATE leads SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            flash_set('success', 'Статус заявки #' . $id . ' изменён на «' . STATUSES[$status] . '».');
        } else {
            flash_set('error', 'Некорректные данные для смены статуса.');
        }
        redirect('index.php?' . http_build_query(array_filter([
            'q'      => $_POST['back_q'] ?? null,
            'page'   => $_POST['back_page'] ?? null,
            'status' => $_POST['back_status'] ?? null,
        ])));

    case 'export':
        $q      = trim((string)($_GET['q'] ?? ''));
        $status = (string)($_GET['status'] ?? '');

        $sql    = 'SELECT id, name, phone, email, message, status, created_at FROM leads';
        $where  = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $like    = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if (isset(STATUSES[$status])) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="leads_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Дата', 'Имя', 'Телефон', 'E-mail', 'Сообщение', 'Статус'], ';');

        while ($row = $stmt->fetch()) {
            fputcsv($out, [
                $row['id'],
                $row['created_at'],
                $row['name'],
                $row['phone'],
                $row['email'],
                $row['message'],
                STATUSES[$row['status']] ?? $row['status'],
            ], ';');
        }
        fclose($out);
        exit;

    case 'logout':
        logout();

    default:
        http_response_code(400);
        exit('Неизвестное действие.');
}