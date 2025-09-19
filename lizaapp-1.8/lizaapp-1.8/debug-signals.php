<?php
// Диагностика сигналов
$signaling_server = 'https://functions.yandexcloud.net/d4ec0rusp5blvc9pucd4';

echo "=== ДИАГНОСТИКА СИГНАЛОВ ===\n";
echo "Сервер: $signaling_server\n\n";

// Получаем все сигналы для user1
echo "1. Сигналы для user1:\n";
$url1 = $signaling_server . '?action=signals&userId=user1&since=0';
$response1 = file_get_contents($url1);
$data1 = json_decode($response1, true);
echo "URL: $url1\n";
echo "Ответ: " . $response1 . "\n\n";

// Получаем все сигналы для user2
echo "2. Сигналы для user2:\n";
$url2 = $signaling_server . '?action=signals&userId=user2&since=0';
$response2 = file_get_contents($url2);
$data2 = json_decode($response2, true);
echo "URL: $url2\n";
echo "Ответ: " . $response2 . "\n\n";

// Отправляем тестовый ping
echo "3. Отправляем тестовый ping от user1 к user2:\n";
$ping_data = json_encode([
    'action' => 'signal',
    'from' => 'user1',
    'to' => 'user2',
    'type' => 'ping',
    'data' => ['test' => true]
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $ping_data
    ]
]);

$ping_response = file_get_contents($signaling_server, false, $context);
echo "Данные ping: $ping_data\n";
echo "Ответ: " . $ping_response . "\n\n";

// Проверяем сигналы после отправки
echo "4. Сигналы для user2 после отправки ping:\n";
$response2_after = file_get_contents($url2);
echo "Ответ: " . $response2_after . "\n\n";

echo "=== КОНЕЦ ДИАГНОСТИКИ ===\n";
?>
