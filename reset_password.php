<?php
require_once 'config/database.php';

echo "🔧 Сброс пароля пользователя...\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Не удалось подключиться к базе данных");
    }
    
    echo "✅ Подключение к базе данных успешно\n\n";
    
    // Сбрасываем пароль для пользователя +79182725362
    $phone = '+79182725362';
    $newPassword = 'password123';
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password_hash = :password_hash WHERE phone = :phone";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':phone', $phone);
    
    if ($stmt->execute()) {
        echo "✅ Пароль для пользователя $phone сброшен на '$newPassword'\n";
        
        // Проверяем, что пароль установлен правильно
        $checkQuery = "SELECT id, phone FROM users WHERE phone = :phone";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':phone', $phone);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            echo "✅ Пользователь найден: ID {$user['id']}, Телефон {$user['phone']}\n";
        }
    } else {
        echo "❌ Ошибка сброса пароля\n";
    }
    
    // Также сбрасываем пароль для второго пользователя
    $phone2 = '+79182725363';
    $query2 = "UPDATE users SET password_hash = :password_hash WHERE phone = :phone";
    $stmt2 = $conn->prepare($query2);
    $stmt2->bindParam(':password_hash', $passwordHash);
    $stmt2->bindParam(':phone', $phone2);
    
    if ($stmt2->execute()) {
        echo "✅ Пароль для пользователя $phone2 сброшен на '$newPassword'\n";
    }
    
    echo "\n🎉 Сброс паролей завершен!\n";
    echo "Теперь можно войти с паролем: $newPassword\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
