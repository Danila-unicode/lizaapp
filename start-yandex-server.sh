#!/bin/bash

# 🚀 Скрипт запуска WebSocket сервера на Yandex Cloud VM

echo "🚀 Запускаем WebSocket сервер на Yandex Cloud VM..."

# Проверяем, что Yandex CLI установлен
if ! command -v yc &> /dev/null; then
    echo "❌ Yandex CLI не установлен. Установите его сначала."
    exit 1
fi

# Получаем список VM
echo "📋 Получаем список VM..."
yc compute instance list

# Запрашиваем ID VM
read -p "Введите ID VM: " VM_ID

# Получаем внешний IP VM
echo "🌐 Получаем внешний IP VM..."
EXTERNAL_IP=$(yc compute instance get $VM_ID --format json | jq -r '.network_interfaces[0].primary_v4_address.one_to_one_nat.address')
echo "Внешний IP: $EXTERNAL_IP"

# Подключаемся к VM и запускаем сервер
echo "🔌 Подключаемся к VM и запускаем сервер..."
ssh ubuntu@$EXTERNAL_IP << 'EOF'

echo "📊 Проверяем статус PM2..."
pm2 status

echo "🚀 Запускаем WebSocket сервер..."
cd /home/ubuntu/webrtc-signaling
pm2 start server.js --name "webrtc-signaling"

echo "⚙️ Настраиваем автозапуск..."
pm2 startup
pm2 save

echo "📊 Проверяем статус после запуска..."
pm2 status

echo "📋 Показываем логи..."
pm2 logs webrtc-signaling --lines 20

EOF

echo "🧪 Тестируем запущенный сервер..."
echo "HTTP API: https://lizamsg.ru:3000/api/stats"
echo "WebSocket: wss://lizamsg.ru:8080"

# Тестируем HTTP API
echo "🔍 Тестируем HTTP API..."
curl -k https://lizamsg.ru:3000/api/stats || echo "⚠️ HTTP API недоступен"

echo "✅ Сервер запущен!"
echo "🌐 HTTP API: https://lizamsg.ru:3000"
echo "🔌 WebSocket: wss://lizamsg.ru:8080"
echo "📊 Статистика: https://lizamsg.ru:3000/api/stats"
