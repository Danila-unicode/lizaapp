#!/bin/bash

# Скрипт для настройки постоянной работы WebSocket сервера

echo "🚀 Настройка постоянной работы WebSocket сервера..."

# Установка PM2
echo "📦 Устанавливаем PM2..."
sudo npm install -g pm2

# Остановка всех процессов Node.js
echo "🛑 Останавливаем все процессы Node.js..."
pkill -f "node server.js" || true

# Переход в папку сервера
cd ~/websocket-server

# Запуск сервера через PM2
echo "🚀 Запускаем сервер через PM2..."
pm2 start server.js --name "webrtc-signaling"

# Настройка автозапуска
echo "⚙️ Настраиваем автозапуск..."
pm2 startup
pm2 save

# Проверка статуса
echo "📊 Проверяем статус..."
pm2 status

echo "✅ Сервер настроен для постоянной работы!"
echo "🌐 HTTP API: https://lizamsg.ru:3000"
echo "🔌 WebSocket: wss://lizamsg.ru:8080"
echo "📊 Статистика: https://lizamsg.ru:3000/api/stats"
