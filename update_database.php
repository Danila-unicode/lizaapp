<?php
// Скрипт обновления базы данных для WebRTC системы
// Подключение к базе данных
$host = 'localhost';
$dbname = 'lizaapp_dsfg12df1121q5sd2694';
$username = 'lizaapp_1w1d2sd3268';
$password = 'aM1oX3yE0j';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Подключение к базе данных установлено\n";
} catch(PDOException $e) {
    die("❌ Ошибка подключения: " . $e->getMessage() . "\n");
}

// SQL для создания таблиц
$sql = "
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

-- Удаление таблицы call_history если она существует
DROP TABLE IF EXISTS call_history;

-- Создание индексов для оптимизации
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);
CREATE INDEX IF NOT EXISTS idx_sessions_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_expires ON user_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_contacts_user_id ON user_contacts(user_id);
CREATE INDEX IF NOT EXISTS idx_contacts_contact_id ON user_contacts(contact_user_id);
";

// Выполнение SQL
try {
    $pdo->exec($sql);
    echo "✅ Таблицы созданы успешно\n";
} catch(PDOException $e) {
    echo "⚠️ Предупреждение при создании таблиц: " . $e->getMessage() . "\n";
}

// Проверка существования тестовых пользователей
$checkUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username IN ('user1', 'user2')");
$checkUsers->execute();
$userCount = $checkUsers->fetchColumn();

if ($userCount == 0) {
    // Вставка тестовых пользователей
    $insertUsers = $pdo->prepare("
        INSERT INTO users (username, password_hash) VALUES 
        (?, ?), (?, ?)
    ");
    
    // Хеш пароля 12345
    $passwordHash = '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    $insertUsers->execute(['user1', $passwordHash, 'user2', $passwordHash]);
    echo "✅ Тестовые пользователи созданы (user1/12345, user2/12345)\n";
    
    // Добавление контактов между тестовыми пользователями
    $insertContacts = $pdo->prepare("
        INSERT INTO user_contacts (user_id, contact_user_id, contact_name) VALUES 
        (1, 2, 'User 2'), (2, 1, 'User 1')
    ");
    $insertContacts->execute();
    echo "✅ Контакты между тестовыми пользователями созданы\n";
} else {
    echo "ℹ️ Тестовые пользователи уже существуют\n";
}

// Проверка структуры базы данных
echo "\n📊 Структура базы данных:\n";
$tables = ['users', 'user_sessions', 'user_contacts'];
foreach ($tables as $table) {
    $result = $pdo->query("DESCRIBE $table");
    echo "\nТаблица: $table\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
}

// Подсчет записей
echo "\n📈 Количество записей:\n";
foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "  - $table: $count записей\n";
}

echo "\n✅ Обновление базы данных завершено успешно!\n";
?>
