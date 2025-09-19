#!/bin/bash

# 🚀 Скрипт развертывания WebSocket сервера на Yandex Cloud VM

echo "🚀 Начинаем развертывание WebSocket сервера на Yandex Cloud VM..."

# Проверяем, что Yandex CLI установлен
if ! command -v yc &> /dev/null; then
    echo "❌ Yandex CLI не установлен. Установите его сначала."
    exit 1
fi

# Получаем список VM
echo "📋 Получаем список VM..."
yc compute instance list

# Запрашиваем ID VM
read -p "Введите ID VM для развертывания: " VM_ID

# Получаем внешний IP VM
echo "🌐 Получаем внешний IP VM..."
EXTERNAL_IP=$(yc compute instance get $VM_ID --format json | jq -r '.network_interfaces[0].primary_v4_address.one_to_one_nat.address')
echo "Внешний IP: $EXTERNAL_IP"

# Подключаемся к VM
echo "🔌 Подключаемся к VM..."
ssh ubuntu@$EXTERNAL_IP << 'EOF'

echo "📦 Обновляем систему..."
sudo apt update && sudo apt upgrade -y

echo "📦 Устанавливаем Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

echo "📦 Устанавливаем PM2..."
sudo npm install -g pm2

echo "📁 Создаем папку для приложения..."
mkdir -p /home/ubuntu/webrtc-signaling
cd /home/ubuntu/webrtc-signaling

echo "📄 Создаем package.json..."
cat > package.json << 'PACKAGE_EOF'
{
  "name": "webrtc-signaling-server",
  "version": "1.0.0",
  "description": "WebSocket signaling server for WebRTC",
  "main": "server.js",
  "scripts": {
    "start": "node server.js",
    "dev": "nodemon server.js"
  },
  "dependencies": {
    "ws": "^8.14.2",
    "express": "^4.18.2",
    "cors": "^2.8.5",
    "uuid": "^9.0.1"
  },
  "devDependencies": {
    "nodemon": "^3.0.1"
  },
  "keywords": ["webrtc", "websocket", "signaling"],
  "author": "WebRTC Team",
  "license": "MIT"
}
PACKAGE_EOF

echo "📦 Устанавливаем зависимости..."
npm install

echo "🔒 Настраиваем файрвол..."
sudo ufw allow 3000/tcp
sudo ufw allow 8080/tcp
sudo ufw allow 22/tcp
sudo ufw --force enable

echo "✅ Готово! Теперь нужно:"
echo "1. Загрузить server.js в /home/ubuntu/webrtc-signaling/"
echo "2. Настроить SSL сертификаты"
echo "3. Запустить сервер через PM2"

EOF

echo "📤 Загружаем server.js на VM..."
scp websocket-server/server.js ubuntu@$EXTERNAL_IP:/home/ubuntu/webrtc-signaling/

echo "🔧 Настраиваем SSL сертификаты..."
ssh ubuntu@$EXTERNAL_IP << 'EOF'

echo "🔒 Устанавливаем Certbot..."
sudo apt install certbot -y

echo "🔒 Получаем SSL сертификат..."
sudo certbot certonly --standalone -d lizamsg.ru --non-interactive --agree-tos --email admin@lizamsg.ru

echo "📁 Создаем папку для SSL..."
sudo mkdir -p /home/ubuntu/ssl

echo "📋 Копируем сертификаты..."
sudo cp /etc/letsencrypt/live/lizamsg.ru/privkey.pem /home/ubuntu/ssl/key.pem
sudo cp /etc/letsencrypt/live/lizamsg.ru/fullchain.pem /home/ubuntu/ssl/cert.pem
sudo chown ubuntu:ubuntu /home/ubuntu/ssl/*.pem

echo "🚀 Запускаем сервер..."
cd /home/ubuntu/webrtc-signaling
pm2 start server.js --name "webrtc-signaling"

echo "⚙️ Настраиваем автозапуск..."
pm2 startup
pm2 save

echo "📊 Проверяем статус..."
pm2 status

EOF

echo "🧪 Тестируем развертывание..."
echo "HTTP API: https://lizamsg.ru:3000/api/stats"
echo "WebSocket: wss://lizamsg.ru:8080"

# Тестируем HTTP API
echo "🔍 Тестируем HTTP API..."
curl -k https://lizamsg.ru:3000/api/stats || echo "⚠️ HTTP API недоступен"

echo "✅ Развертывание завершено!"
echo "🌐 Сервер доступен по адресу: https://lizamsg.ru:3000"
echo "🔌 WebSocket доступен по адресу: wss://lizamsg.ru:8080"
echo "📊 Статистика: https://lizamsg.ru:3000/api/stats"
