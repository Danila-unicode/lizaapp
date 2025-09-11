<?php
require_once 'config/database.php';

echo "🔧 Настройка базы данных...\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Не удалось подключиться к базе данных");
    }
    
    echo "✅ Подключение к базе данных успешно\n";
    
    // Проверяем существование таблиц
    $tables = ['users', 'contacts', 'calls'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Таблица '$table' существует\n";
        } else {
            echo "❌ Таблица '$table' не найдена\n";
        }
    }
    
    // Создаем тестовых пользователей
    echo "\n👥 Создание тестовых пользователей...\n";
    
    $testUsers = [
        ['phone' => '+79182725362', 'password' => 'password123'],
        ['phone' => '+79182725363', 'password' => 'password123'],
        ['phone' => '+79001111111', 'password' => 'password123']
    ];
    
    foreach ($testUsers as $user) {
        // Проверяем, существует ли пользователь
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE phone = :phone");
        $checkStmt->bindParam(":phone", $user['phone']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            echo "⚠️  Пользователь {$user['phone']} уже существует\n";
        } else {
            $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO users (phone, password_hash) VALUES (:phone, :password_hash)");
            $insertStmt->bindParam(":phone", $user['phone']);
            $insertStmt->bindParam(":password_hash", $password_hash);
            
            if ($insertStmt->execute()) {
                echo "✅ Пользователь {$user['phone']} создан (пароль: {$user['password']})\n";
            } else {
                echo "❌ Ошибка создания пользователя {$user['phone']}\n";
            }
        }
    }
    
    // Показываем всех пользователей
    echo "\n📋 Список всех пользователей:\n";
    $stmt = $conn->query("SELECT id, phone, created_at FROM users ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Телефон: {$row['phone']}, Создан: {$row['created_at']}\n";
    }
    
    echo "\n🎉 Настройка базы данных завершена!\n";
    echo "\n📝 Тестовые данные для входа:\n";
    echo "Пользователь 1: +79182725362 / password123\n";
    echo "Пользователь 2: +79182725363 / password123\n";
    echo "Пользователь 3: +79001111111 / password123\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
