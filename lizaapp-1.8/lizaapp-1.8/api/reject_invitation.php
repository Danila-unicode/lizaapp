<?php
header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$senderUsername = $data['sender_username'] ?? '';

if(empty($senderUsername)) {
    echo json_encode(['success' => false, 'message' => 'Логин отправителя не указан']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Ошибка подключения к базе данных');
    }
    
    // Найти ID отправителя по логину
    $query = "SELECT id FROM users WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":username", $senderUsername);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit();
    }
    
    $sender = $stmt->fetch(PDO::FETCH_ASSOC);
    $contact_id = $sender['id'];
    
    // Обновляем статус запроса на 'rejected'
    $query = "UPDATE contacts 
              SET status = 'rejected' 
              WHERE user_id = :contact_id AND contact_id = :user_id AND status = 'pending'";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':contact_id', $contact_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Запрос отклонен'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Запрос не найден или уже обработан'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
