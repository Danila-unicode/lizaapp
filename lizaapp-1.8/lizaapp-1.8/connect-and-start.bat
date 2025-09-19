@echo off
echo 🚀 Подключение к Yandex Cloud VM и запуск WebSocket сервера

echo 🔌 Подключаемся к VM...
ssh ubuntu@51.250.11.172

echo.
echo После подключения выполните команды:
echo cd /home/ubuntu/webrtc-signaling
echo pm2 start server.js --name "webrtc-signaling"
echo pm2 status
echo pm2 logs webrtc-signaling --lines 20

pause
