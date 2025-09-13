<?php
require_once 'config/database.php';

echo "<h1>Обновление базы данных</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Ошибка подключения к базе данных');
    }
    
    echo "<p>✅ Подключение к базе данных успешно</p>";
    
    // Проверяем, существует ли таблица users
    $checkTable = "SHOW TABLES LIKE 'users'";
    $stmt = $conn->prepare($checkTable);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Таблица users существует</p>";
        
        // Проверяем структуру таблицы
        $describeTable = "DESCRIBE users";
        $stmt = $conn->prepare($describeTable);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Структура таблицы users:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Проверяем, есть ли поле phone
        $hasPhone = false;
        $hasPasswordHash = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'phone') $hasPhone = true;
            if ($column['Field'] === 'password_hash') $hasPasswordHash = true;
        }
        
        if (!$hasPhone) {
            echo "<p>⚠️ Поле 'phone' отсутствует. Добавляем...</p>";
            $alterQuery = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) UNIQUE AFTER id";
            $conn->exec($alterQuery);
            echo "<p>✅ Поле 'phone' добавлено</p>";
        } else {
            echo "<p>✅ Поле 'phone' существует</p>";
        }
        
        if (!$hasPasswordHash) {
            echo "<p>⚠️ Поле 'password_hash' отсутствует. Добавляем...</p>";
            $alterQuery = "ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) AFTER phone";
            $conn->exec($alterQuery);
            echo "<p>✅ Поле 'password_hash' добавлено</p>";
        } else {
            echo "<p>✅ Поле 'password_hash' существует</p>";
        }
        
        // Проверяем, есть ли поле login (старое поле)
        $hasLogin = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'login') $hasLogin = true;
        }
        
        if ($hasLogin) {
            echo "<p>⚠️ Найдено старое поле 'login'. Можно удалить после миграции данных.</p>";
        }
        
    } else {
        echo "<p>❌ Таблица users не существует. Создаем...</p>";
        
        $createTable = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->exec($createTable);
        echo "<p>✅ Таблица users создана</p>";
    }
    
    // Проверяем таблицу active_sessions
    $checkSessions = "SHOW TABLES LIKE 'active_sessions'";
    $stmt = $conn->prepare($checkSessions);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Таблица active_sessions существует</p>";
    } else {
        echo "<p>⚠️ Таблица active_sessions не существует. Создаем...</p>";
        
        $createSessions = "CREATE TABLE active_sessions (
            user_id INT NOT NULL,
            session_token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $conn->exec($createSessions);
        echo "<p>✅ Таблица active_sessions создана</p>";
    }
    
    // Создаем индексы
    echo "<p>Создаем индексы...</p>";
    
    try {
        $conn->exec("CREATE INDEX idx_users_phone ON users(phone)");
        echo "<p>✅ Индекс idx_users_phone создан</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ Индекс idx_users_phone уже существует</p>";
    }
    
    try {
        $conn->exec("CREATE INDEX idx_active_sessions_user_id ON active_sessions(user_id)");
        echo "<p>✅ Индекс idx_active_sessions_user_id создан</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ Индекс idx_active_sessions_user_id уже существует</p>";
    }
    
    try {
        $conn->exec("CREATE INDEX idx_active_sessions_token ON active_sessions(session_token)");
        echo "<p>✅ Индекс idx_active_sessions_token создан</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ Индекс idx_active_sessions_token уже существует</p>";
    }
    
    echo "<h2>✅ Обновление базы данных завершено успешно!</h2>";
    echo "<p><a href='main.html'>Перейти на главную страницу</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Ошибка: " . $e->getMessage() . "</p>";
}
?>
