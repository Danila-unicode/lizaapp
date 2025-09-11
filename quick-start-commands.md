# 🚀 Быстрый запуск WebSocket сервера на Yandex Cloud VM

## 📋 Команды для запуска

### 1. Получить список VM
```bash
yc compute instance list
```

### 2. Получить внешний IP VM
```bash
yc compute instance get <instance-id> --format json | jq -r '.network_interfaces[0].primary_v4_address.one_to_one_nat.address'
```

### 3. Подключиться к VM и запустить сервер
```bash
ssh ubuntu@<external-ip>
cd /home/ubuntu/webrtc-signaling
pm2 start server.js --name "webrtc-signaling"
pm2 status
```

### 4. Проверить логи
```bash
pm2 logs webrtc-signaling --lines 20
```

### 5. Проверить статистику
```bash
curl https://lizamsg.ru:3000/api/stats
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
