@echo off
echo 🚀 Запуск WebSocket сервера на Yandex Cloud VM

echo 📋 Получаем список VM...
yc compute instance list

echo.
echo Введите ID VM для запуска сервера:
set /p VM_ID=

echo 🌐 Получаем внешний IP VM...
for /f "tokens=*" %%i in ('yc compute instance get %VM_ID% --format json ^| jq -r ".network_interfaces[0].primary_v4_address.one_to_one_nat.address"') do set EXTERNAL_IP=%%i

echo Внешний IP: %EXTERNAL_IP%

echo 🔌 Подключаемся к VM и запускаем сервер...
ssh ubuntu@%EXTERNAL_IP% "cd /home/ubuntu/webrtc-signaling && pm2 start server.js --name webrtc-signaling && pm2 status"

echo.
echo 🧪 Тестируем запущенный сервер...
echo HTTP API: https://lizamsg.ru:3000/api/stats
echo WebSocket: wss://lizamsg.ru:8080

echo.
echo ✅ Сервер запущен!
echo 🌐 HTTP API: https://lizamsg.ru:3000
echo 🔌 WebSocket: wss://lizamsg.ru:8080
echo 📊 Статистика: https://lizamsg.ru:3000/api/stats

pause
