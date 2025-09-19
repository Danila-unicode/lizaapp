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
$contactId = $data['contact_id'] ?? 0;

if($contactId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID контакта обязателен']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
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
