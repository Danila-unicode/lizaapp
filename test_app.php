<?php
require_once 'config/database.php';

echo "🧪 Тестирование приложения WebRTC\n\n";

// Тест 1: Подключение к базе данных
echo "1. Тест подключения к базе данных...\n";
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "✅ База данных подключена успешно\n";
    } else {
        echo "❌ Ошибка подключения к базе данных\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

// Тест 2: Проверка таблиц
echo "\n2. Проверка структуры базы данных...\n";
$tables = ['users', 'contacts', 'calls'];
foreach ($tables as $table) {
    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Таблица '$table' существует\n";
        
        // Показываем количество записей
        $countStmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   📊 Записей в таблице: $count\n";
    } else {
        echo "❌ Таблица '$table' не найдена\n";
    }
}

// Тест 3: Проверка API endpoints
echo "\n3. Проверка API endpoints...\n";

$apiFiles = [
    'api/signaling_server.php',
    'api/search_user.php',
    'api/send_invitation.php',
    'api/accept_invitation.php',
    'api/get_contacts.php',
    'api/get_requests.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file существует\n";
    } else {
        echo "❌ $file не найден\n";
    }
}

// Тест 4: Проверка JavaScript файлов
echo "\n4. Проверка JavaScript файлов...\n";

$jsFiles = [
    'assets/js/app.js',
    'assets/js/webrtc-http.js',
    'assets/js/webrtc.js'
];

foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file существует\n";
    } else {
        echo "❌ $file не найден\n";
    }
}

// Тест 5: Проверка HTML страниц
echo "\n5. Проверка HTML страниц...\n";

$htmlFiles = [
    'index.php',
    'login.php',
    'register.php',
    'webrtc-demo.html',
    'webrtc-demo-fixed.html'
];

foreach ($htmlFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file существует\n";
    } else {
        echo "❌ $file не найден\n";
    }
}

// Тест 6: Проверка сигналинг сервера
echo "\n6. Тест сигналинг сервера...\n";

$signalingUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/signaling_server.php?action=status';

$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($signalingUrl, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✅ Сигналинг сервер работает\n";
        echo "   📊 Всего сигналов: " . ($data['totalSignals'] ?? 0) . "\n";
    } else {
        echo "❌ Сигналинг сервер не отвечает корректно\n";
    }
} else {
    echo "❌ Не удалось подключиться к сигналинг серверу\n";
}

// Тест 7: Проверка файла сигналов
echo "\n7. Проверка файла сигналов...\n";

$signalsFile = 'signals.json';
if (file_exists($signalsFile)) {
    $signalsData = json_decode(file_get_contents($signalsFile), true);
    if ($signalsData) {
        echo "✅ Файл сигналов существует и читается\n";
        echo "   📊 Сигналов в файле: " . count($signalsData['signals'] ?? []) . "\n";
        echo "   🔢 Последний ID: " . ($signalsData['lastSignalId'] ?? 0) . "\n";
    } else {
        echo "❌ Файл сигналов поврежден\n";
    }
} else {
    echo "⚠️  Файл сигналов не существует (будет создан автоматически)\n";
}

// Тест 8: Проверка прав доступа
echo "\n8. Проверка прав доступа...\n";

$writableDirs = [
    '.',
    'api',
    'assets'
];

foreach ($writableDirs as $dir) {
    if (is_writable($dir)) {
        echo "✅ Директория '$dir' доступна для записи\n";
    } else {
        echo "❌ Директория '$dir' недоступна для записи\n";
    }
}

echo "\n🎉 Тестирование завершено!\n\n";

echo "📋 Рекомендации:\n";
echo "1. Запустите setup_database.php для создания тестовых пользователей\n";
echo "2. Откройте webrtc-demo-fixed.html для тестирования WebRTC\n";
echo "3. Используйте тестовые данные: +79001234567 / password123\n";
echo "4. Проверьте консоль браузера на наличие ошибок\n";
?>
