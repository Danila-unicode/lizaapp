-- База данных для системы WebRTC с личными кабинетами
-- Создание базы данных
CREATE DATABASE IF NOT EXISTS lizaapp_dsfg12df1121q5sd2694;
USE lizaapp_dsfg12df1121q5sd2694;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Таблица активных сессий
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица контактов пользователей
CREATE TABLE IF NOT EXISTS user_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_user_id INT NOT NULL,
    contact_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contact (user_id, contact_user_id)
);


-- Создание индексов для оптимизации
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_active ON users(is_active);
CREATE INDEX idx_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);
CREATE INDEX idx_contacts_user_id ON user_contacts(user_id);
CREATE INDEX idx_contacts_contact_id ON user_contacts(contact_user_id);

-- Вставка тестовых пользователей
-- Пароли: user1=12345, user2=12345 (хеши bcrypt)
INSERT INTO users (username, password_hash) VALUES 
('user1', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- пароль: 12345
('user2', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- пароль: 12345

-- Добавление контактов между тестовыми пользователями
INSERT INTO user_contacts (user_id, contact_user_id, contact_name) VALUES 
(1, 2, 'User 2'),
(2, 1, 'User 1');

-- Создание пользователя для подключения к базе данных
CREATE USER IF NOT EXISTS 'lizaapp_1w1d2sd3268'@'localhost' IDENTIFIED BY 'aM1oX3yE0j';
GRANT ALL PRIVILEGES ON lizaapp_dsfg12df1121q5sd2694.* TO 'lizaapp_1w1d2sd3268'@'localhost';
FLUSH PRIVILEGES;

-- Вывод информации о созданных пользователях
SELECT 'База данных создана успешно!' as status;
SELECT id, username, created_at, is_active FROM users;
