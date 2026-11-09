<?php
declare(strict_types=1);

/**
 * Конфигурация приложения.
 * Для локального XAMPP: пользователь root, пароль пустой.
 * В продакшене — обязательно поменяйте пароль БД и APP_SECRET.
 */

// ---- База данных ----
const DB_HOST = 'localhost';
const DB_NAME = 'site_db';
const DB_USER = 'root';
const DB_PASS = '';

// ---- Приложение ----
// Секретный ключ для CSRF и подписи сессий. ЗАМЕНИТЕ в продакшене!
const APP_SECRET = 'change-me-please-2f8a1c9e7b4d6f0a3e5c8b2d9f1a4e7c';

// ---- Безопасность ----
const MAX_LOGIN_ATTEMPTS = 5;     // попыток до временной блокировки
const LOGIN_LOCK_SECONDS = 300;   // 5 минут блокировки
const LOGIN_SLEEP_SECONDS = 1;    // задержка при неудаче (антибрутфорс)

// ---- Отображение ----
const PER_PAGE = 20;              // заявок на страницу
const STATUSES = [
    'new'         => 'Новая',
    'in_progress' => 'В работе',
    'done'        => 'Обработана',
    'spam'        => 'Спам',
];

// ---- Режим отладки ----
// В продакшене: false (детали ошибок не показываем пользователю)
const APP_DEBUG = true;