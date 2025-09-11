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
$contact_id = $data['contact_id'] ?? null;

if (!$contact_id) {
    echo json_encode(['success' => false, 'message' => 'ID контакта не указан']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Ошибка подключения к базе данных');
    }
    
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
