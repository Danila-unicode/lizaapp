# 🚀 Развертывание WebSocket сервера на Yandex Cloud VM

## 📋 Предварительные требования

- ✅ **Yandex CLI** установлен и настроен
- ✅ **VM в Yandex Cloud** создана и доступна
- ✅ **SSH доступ** к VM
- ✅ **Node.js** установлен на VM

## 🔧 Пошаговое развертывание

### Шаг 1: Подключение к VM через Yandex CLI

```bash
# Получить список VM
yc compute instance list

# Подключиться к VM по SSH
yc compute instance get <instance-id> --format json | jq -r '.network_interfaces[0].primary_v4_address.one_to_one_nat.address'
ssh ubuntu@<external-ip>
```

### Шаг 2: Подготовка сервера на VM

```bash
# Обновить систему
sudo apt update && sudo apt upgrade -y

# Установить Node.js (если не установлен)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Установить PM2 для управления процессами
sudo npm install -g pm2

# Создать папку для приложения
mkdir -p /home/ubuntu/webrtc-signaling
cd /home/ubuntu/webrtc-signaling
```

### Шаг 3: Загрузка файлов сервера

```bash
# Создать package.json
cat > package.json << 'EOF'
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
EOF

# Установить зависимости
npm install
```

### Шаг 4: Создание server.js

```bash
# Создать server.js (скопировать содержимое из локального файла)
# Или загрузить через scp с локальной машины
```

### Шаг 5: Настройка SSL сертификатов

```bash
# Создать папку для SSL
sudo mkdir -p /home/ubuntu/ssl

# Получить Let's Encrypt сертификат
sudo apt install certbot
sudo certbot certonly --standalone -d lizamsg.ru

# Скопировать сертификаты
sudo cp /etc/letsencrypt/live/lizamsg.ru/privkey.pem /home/ubuntu/ssl/key.pem
sudo cp /etc/letsencrypt/live/lizamsg.ru/fullchain.pem /home/ubuntu/ssl/cert.pem
sudo chown ubuntu:ubuntu /home/ubuntu/ssl/*.pem
```

### Шаг 6: Настройка файрвола

```bash
# Открыть порты 3000 и 8080
sudo ufw allow 3000/tcp
sudo ufw allow 8080/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

### Шаг 7: Запуск сервера

```bash
# Запустить сервер через PM2
pm2 start server.js --name "webrtc-signaling"

# Настроить автозапуск
pm2 startup
pm2 save

# Проверить статус
pm2 status
pm2 logs webrtc-signaling
```

## 🔍 Проверка работы

### 1. Проверка HTTP API
```bash
curl http://localhost:3000/api/stats
```

### 2. Проверка WebSocket
```bash
# Установить wscat для тестирования WebSocket
npm install -g wscat

# Подключиться к WebSocket
wscat -c wss://lizamsg.ru:8080
```

### 3. Проверка с внешнего IP
```bash
# Получить внешний IP VM
curl ifconfig.me

# Проверить доступность портов
telnet <external-ip> 3000
telnet <external-ip> 8080
```

## 🧪 Тестирование

### 1. Локальное тестирование на VM
```bash
# Открыть тестовую страницу
curl http://localhost:3000/test.html
```

### 2. Внешнее тестирование
```
https://lizamsg.ru:3000/test.html
```

### 3. Тестирование WebSocket
```javascript
const ws = new WebSocket('wss://lizamsg.ru:8080');
ws.onopen = () => console.log('Connected');
ws.onmessage = (event) => console.log('Message:', event.data);
```

## 📊 Мониторинг

### 1. Статистика PM2
```bash
pm2 monit
```

### 2. Логи сервера
```bash
pm2 logs webrtc-signaling --lines 100
```

### 3. Системные ресурсы
```bash
htop
df -h
free -h
```

## 🔧 Управление сервером

### Остановка сервера
```bash
pm2 stop webrtc-signaling
```

### Перезапуск сервера
```bash
pm2 restart webrtc-signaling
```

### Обновление сервера
```bash
# Остановить сервер
pm2 stop webrtc-signaling

# Обновить код
# (загрузить новый server.js)

# Запустить сервер
pm2 start webrtc-signaling
```

## 🚨 Устранение неполадок

### Проблема: Сервер не запускается
```bash
# Проверить логи
pm2 logs webrtc-signaling

# Проверить порты
netstat -tulpn | grep :3000
netstat -tulpn | grep :8080
```

### Проблема: SSL сертификаты не работают
```bash
# Проверить сертификаты
openssl x509 -in /home/ubuntu/ssl/cert.pem -text -noout

# Обновить сертификаты
sudo certbot renew
```

### Проблема: Порт недоступен
```bash
# Проверить файрвол
sudo ufw status

# Проверить процессы
sudo lsof -i :3000
sudo lsof -i :8080
```

## 🎯 Готово!

После выполнения всех шагов WebSocket сервер будет работать на:
- **HTTP API:** `https://lizamsg.ru:3000`
- **WebSocket:** `wss://lizamsg.ru:8080`

Сервер готов к тестированию с `simple-signal-test-websocket.html`!
