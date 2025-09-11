<?php
header('Content-Type: application/json');
session_start();

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'] ?? '';
$password = $data['password'] ?? '';

if(empty($phone) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Номер телефона и пароль обязательны']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Ошибка подключения к базе данных');
    }
    
    // Найти пользователя по номеру телефона
    $query = "SELECT id, phone, password_hash FROM users WHERE phone = :phone";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Проверяем пароль
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Неверный пароль']);
        exit();
    }
    
    // Создаем сессию
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_phone'] = $user['phone'];
    $_SESSION['logged_in'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Вход выполнен успешно',
        'user' => [
            'id' => $user['id'],
            'phone' => $user['phone']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
}
?>
