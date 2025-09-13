-- Создание базы данных для системы видеозвонков
CREATE DATABASE lizaapp_fgdg1c1d551v1d CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Создание пользователя
CREATE USER 'lizaapp_q2f112f1c'@'%' IDENTIFIED BY 'mS2rJ7uK5r';
GRANT ALL PRIVILEGES ON lizaapp_fgdg1c1d551v1d.* TO 'lizaapp_q2f112f1c'@'%';
FLUSH PRIVILEGES;

-- Использование базы данных
USE lizaapp_fgdg1c1d551v1d;

-- Создание таблицы пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Создание таблицы активных сессий
CREATE TABLE active_sessions (
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
