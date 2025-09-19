<?php
header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit();
}

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$senderUsername = $data['sender_username'] ?? '';

if(empty($senderUsername)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Логин отправителя обязателен']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
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
    $contactId = $sender['id'];
    
    // Принять приглашение
    $query = "UPDATE contacts SET status = 'accepted' WHERE user_id = :contact_id AND contact_id = :user_id AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->bindParam(":contact_id", $contactId);
    
    if($stmt->execute() && $stmt->rowCount() > 0) {
        // Создать обратную связь
        $query = "INSERT INTO contacts (user_id, contact_id, status) VALUES (:user_id, :contact_id, 'accepted')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":contact_id", $contactId);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Приглашение принято']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Приглашение не найдено или уже принято']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
