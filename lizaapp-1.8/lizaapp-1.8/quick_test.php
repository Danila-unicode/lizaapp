<?php
echo "🧪 Быстрая проверка WebRTC приложения\n\n";

// Проверка 1: PHP версия
echo "1. Проверка PHP версии...\n";
echo "   PHP версия: " . phpversion() . "\n";
if (version_compare(phpversion(), '7.4.0', '>=')) {
    echo "   ✅ PHP версия подходящая\n";
} else {
    echo "   ❌ Требуется PHP 7.4+\n";
}

// Проверка 2: Расширения PHP
echo "\n2. Проверка расширений PHP...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'curl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext - установлено\n";
    } else {
        echo "   ❌ $ext - не установлено\n";
    }
}

// Проверка 3: Подключение к базе данных
echo "\n3. Проверка подключения к базе данных...\n";
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "   ✅ Подключение к базе данных успешно\n";
        
        // Проверка таблиц
        $tables = ['users', 'contacts', 'calls'];
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "   ✅ Таблица '$table' существует\n";
            } else {
                echo "   ❌ Таблица '$table' не найдена\n";
            }
        }
    } else {
        echo "   ❌ Ошибка подключения к базе данных\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

// Проверка 4: Файлы проекта
echo "\n4. Проверка файлов проекта...\n";
$required_files = [
    'index.php',
    'login.php', 
    'register.php',
    'webrtc-demo-cloud.html',
    'assets/js/webrtc-http.js',
    'assets/js/app.js',
    'assets/css/style.css'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file - существует\n";
    } else {
        echo "   ❌ $file - не найден\n";
    }
}

// Проверка 5: Yandex Cloud Functions
echo "\n5. Проверка Yandex Cloud Functions...\n";
$signalingUrl = 'https://functions.yandexcloud.net/d4ec0rusp5blvc9pucd4?action=status';

$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($signalingUrl, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "   ✅ Yandex Cloud Functions доступен\n";
    } else {
        echo "   ⚠️  Yandex Cloud Functions отвечает, но с ошибкой\n";
    }
} else {
    echo "   ❌ Yandex Cloud Functions недоступен\n";
}

// Проверка 6: TURN сервер
echo "\n6. Проверка TURN сервера VK Cloud...\n";
$turnHost = '109.120.183.43';
$turnPort = 3478;

$connection = @fsockopen($turnHost, $turnPort, $errno, $errstr, 3);

if ($connection) {
    echo "   ✅ TURN сервер VK Cloud доступен ($turnHost:$turnPort)\n";
    fclose($connection);
} else {
    echo "   ❌ TURN сервер VK Cloud недоступен ($turnHost:$turnPort)\n";
}

echo "\n🎉 Проверка завершена!\n\n";

echo "📋 Следующие шаги:\n";
echo "1. Если есть ошибки - исправьте их\n";
echo "2. Запустите setup_database.php для создания тестовых пользователей\n";
echo "3. Откройте webrtc-demo-cloud.html для тестирования WebRTC\n";
echo "4. Используйте тестовые данные: ID 1 (+79182725362), ID 2 (+79182725363)\n";
?>
