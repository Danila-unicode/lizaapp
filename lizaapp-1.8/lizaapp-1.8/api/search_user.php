<?php
header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit();
}

require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $phone = $input['phone'] ?? '';
    
    if(empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Номер телефона не указан']);
        exit();
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Ищем пользователя по номеру телефона
    $query = "SELECT id, phone FROM users WHERE phone = :phone AND id != :current_user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":current_user_id", $_SESSION['user_id']);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Проверяем, не отправляли ли уже приглашение
        $query = "SELECT id FROM contacts WHERE user_id = :user_id AND contact_id = :contact_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":contact_id", $user['id']);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Приглашение уже отправлено этому пользователю']);
        } else {
            echo json_encode(['success' => true, 'user' => $user]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
