<?php
require_once 'config/database.php';

$message = '';

if($_POST) {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(empty($phone) || empty($password)) {
        $message = 'Заполните все поля';
    } elseif(strlen($password) < 6) {
        $message = 'Пароль должен содержать минимум 6 символов';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            if (!$conn) {
                $message = 'Ошибка подключения к базе данных';
            } else {
                // Проверяем, существует ли пользователь
                $checkQuery = "SELECT id FROM users WHERE phone = :phone";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bindParam(":phone", $phone);
                $checkStmt->execute();
                
                if($checkStmt->rowCount() > 0) {
                    $message = 'Пользователь с таким номером телефона уже существует';
                } else {
                    // Создаем пользователя
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $query = "INSERT INTO users (phone, password_hash) VALUES (:phone, :password_hash)";
                    $stmt = $conn->prepare($query);
                    
                    $stmt->bindParam(":phone", $phone);
                    $stmt->bindParam(":password_hash", $password_hash);
                    
                    if($stmt->execute()) {
                        $message = 'Регистрация успешна! Теперь войдите в систему.';
                        $phone = '';
                        $password = '';
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        $message = 'Ошибка при регистрации: ' . $errorInfo[2];
                    }
                }
            }
        } catch(PDOException $e) {
            $message = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - WebRTC Звонки</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Регистрация</h1>
            
            <?php if($message): ?>
                <div class="message error"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="phone">Номер телефона:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
            </form>
            
            <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
        </div>
    </div>
</body>
</html>