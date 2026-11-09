CREATE DATABASE IF NOT EXISTS site_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE site_db;

CREATE TABLE IF NOT EXISTS leads (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    phone      VARCHAR(40)  NOT NULL,
    email      VARCHAR(190) NOT NULL DEFAULT '',
    message    TEXT         NOT NULL,
    status     ENUM('new','in_progress','done','spam') NOT NULL DEFAULT 'new',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id            INT U