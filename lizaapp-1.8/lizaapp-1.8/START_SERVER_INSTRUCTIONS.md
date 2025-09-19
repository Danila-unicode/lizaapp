# 🚀 Запуск WebSocket сервера на Yandex Cloud VM

## 📋 Команды для запуска

### 1. Подключение к VM
```bash
ssh ubuntu@51.250.11.172
```

### 2. Переход в папку сервера
```bash
cd /home/ubuntu/webrtc-signaling
```

### 3. Запуск сервера
```bash
pm2 start server.js --name "webrtc-signaling"
```

### 4. Проверка статуса
```bash
pm2 status
```

### 5. Просмотр логов
```bash
pm2 logs webrtc-signaling --lines 20
```

## 🔧 Управление сервером

### Остановить сервер
```bash
pm2 stop webrtc-signaling
```

### Перезапустить сервер
```bash
pm2 restart webrtc-signaling
```

### Показать статус
```bash
pm2 status
```

### Показать логи
```bash
pm2 logs webrtc-signaling
```

## 🧪 Тестирование

### HTTP API
```bash
curl https://lizamsg.ru:3000/api/stats
```

### WebSocket (через wscat)
```bash
npm install -g wscat
wscat -c wss://lizamsg.ru:8080
```

### Тестовая страница
```
https://lizamsg.ru:3000/test.html
```

## 📊 Мониторинг

### PM2 мониторинг
```bash
pm2 monit
```

### Системные ресурсы
```bash
htop
df -h
free -h
```

## 🎯 Готово!

После запуска сервер будет доступен:
- **HTTP API:** `https://lizamsg.ru:3000`
- **WebSocket:** `wss://lizamsg.ru:8080`
- **Статистика:** `https://lizamsg.ru:3000/api/stats`

Теперь можно тестировать `simple-signal-test-websocket.html`!
