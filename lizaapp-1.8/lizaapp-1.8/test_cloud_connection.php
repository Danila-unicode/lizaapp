<?php
echo "☁️ Тестирование подключения к Yandex Cloud Functions\n\n";

// URL Yandex Cloud Functions
$signalingUrl = 'https://functions.yandexcloud.net/d4ec0rusp5blvc9pucd4';

// Тест 1: Проверка доступности сервера
echo "1. Проверка доступности Yandex Cloud Functions...\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = @file_get_contents($signalingUrl . '?action=status', false, $context);

if ($response !== false) {
    echo "✅ Yandex Cloud Functions доступен\n";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "📊 Ответ сервера:\n";
        foreach ($data as $key => $value) {
            echo "   $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    } else {
        echo "⚠️  Сервер отвечает, но JSON невалиден\n";
    }
} else {
    echo "❌ Yandex Cloud Functions недоступен\n";
    echo "   Проверьте интернет соединение и URL\n";
}

// Тест 2: Тест отправки сигнала
echo "\n2. Тест отправки сигнала...\n";

$testSignal = [
    'action' => 'signal',
    'roomId' => 'test_room',
    'from' => 'test_user_1',
    'to' => 'test_user_2',
    'type' => 'test',
    'data' => ['message' => 'test signal']
];

$postData = json_encode($testSignal);

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$response = @file_get_contents($signalingUrl, false, $context);

if ($response !== false) {
    echo "✅ Сигнал отправлен успешно\n";
    
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "📊 Ответ сервера: " . json_encode($data) . "\n";
    } else {
        echo "⚠️  Сервер вернул ошибку: " . json_encode($data) . "\n";
    }
} else {
    echo "❌ Ошибка отправки сигнала\n";
}

// Тест 3: Тест получения сигналов
echo "\n3. Тест получения сигналов...\n";

$signalsUrl = $signalingUrl . '?action=signals&roomId=test_room&userId=test_user_2&since=0';

$response = @file_get_contents($signalsUrl, false, $context);

if ($response !== false) {
    echo "✅ Запрос сигналов выполнен\n";
    
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "📊 Получено сигналов: " . count($data['signals'] ?? []) . "\n";
        if (!empty($data['signals'])) {
            foreach ($data['signals'] as $signal) {
                echo "   - {$signal['type']} от {$signal['from']} к {$signal['to']}\n";
            }
        }
    } else {
        echo "⚠️  Сервер вернул ошибку: " . json_encode($data) . "\n";
    }
} else {
    echo "❌ Ошибка получения сигналов\n";
}

// Тест 4: Проверка TURN сервера
echo "\n4. Проверка TURN сервера VK Cloud...\n";

$turnHost = '109.120.183.43';
$turnPort = 3478;

$connection = @fsockopen($turnHost, $turnPort, $errno, $errstr, 5);

if ($connection) {
    echo "✅ TURN сервер VK Cloud доступен ($turnHost:$turnPort)\n";
    fclose($connection);
} else {
    echo "❌ TURN сервер VK Cloud недоступен ($turnHost:$turnPort)\n";
    echo "   Ошибка: $errstr ($errno)\n";
}

// Тест 5: Проверка STUN сервера
echo "\n5. Проверка STUN сервера Google...\n";

$stunHost = 'stun.l.google.com';
$stunPort = 19302;

$connection = @fsockopen($stunHost, $stunPort, $errno, $errstr, 5);

if ($connection) {
    echo "✅ STUN сервер Google доступен ($stunHost:$stunPort)\n";
    fclose($connection);
} else {
    echo "❌ STUN сервер Google недоступен ($stunHost:$stunPort)\n";
    echo "   Ошибка: $errstr ($errno)\n";
}

echo "\n🎉 Тестирование завершено!\n\n";

echo "📋 Рекомендации:\n";
echo "1. Если Yandex Cloud Functions недоступен - проверьте URL и интернет\n";
echo "2. Если TURN сервер недоступен - проверьте настройки VK Cloud\n";
echo "3. Для тестирования WebRTC откройте webrtc-demo-cloud.html\n";
echo "4. Используйте разные ID пользователей в разных вкладках\n";
?>
