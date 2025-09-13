-- Структура базы данных для системы видеозвонков
-- Импортируйте этот файл в существующую базу данных lizaapp_fgdg1c1d551v1d

-- Использование базы данных
USE lizaapp_fgdg1c1d551v1d;

-- Создание таблицы пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Создание таблицы активных сессий
CREATE TABLE IF NOT EXISTS active_sessions (
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Создание индексов для оптимизации
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_active_sessions_user_id ON active_sessions(user_id);
CREATE INDEX idx_active_sessions_token ON active_sessions(session_token);
