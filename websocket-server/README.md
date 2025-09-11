# WebSocket Signaling Server

WebSocket сервер для сигналинга WebRTC соединений.

## Возможности

- ✅ **WebSocket соединения** - real-time обмен сообщениями
- ✅ **Ping/Pong** - проверка соединения между пользователями
- ✅ **Offer/Answer** - обмен SDP для WebRTC
- ✅ **ICE кандидаты** - поддержка NAT traversal
- ✅ **Disconnect** - разрыв соединения
- ✅ **Статистика** - мониторинг подключенных пользователей
- ✅ **Логирование** - подробные логи всех операций

## Установка

1. **Установить зависимости:**
```bash
npm install
```

2. **Запустить сервер:**
```bash
npm start
```

3. **Для разработки (с автоперезагрузкой):**
```bash
npm run dev
```

## Использование

### Запуск сервера
```bash
node server.js
```

Сервер запустится на:
- **HTTP:** http://localhost:3000
- **WebSocket:** ws://localhost:8080
- **Статистика:** http://localhost:3000/api/stats

### Тестирование
Откройте http://localhost:3000/test.html для тестирования WebSocket соединений.

## API

### WebSocket сообщения

#### Подключение
```javascript
const ws = new WebSocket('ws://localhost:8080');
```

#### Ping/Pong
```javascript
// Отправка ping
ws.send(JSON.stringify({
    type: 'ping',
    to: 'targetUserId',
    data: { test: true }
}));

// Отправка pong
ws.send(JSON.stringify({
    type: 'pong',
    to: 'targetUserId',
    data: { received: true }
}));
```

#### Offer/Answer
```javascript
// Отправка offer
ws.send(JSON.stringify({
    type: 'offer',
    to: 'targetUserId',
    data: {
        type: 'offer',
        sdp: 'v=0\r\no=- 1234567890...'
    }
}));

// Отправка answer
ws.send(JSON.stringify({
    type: 'answer',
    to: 'targetUserId',
    data: {
        type: 'answer',
        sdp: 'v=0\r\no=- 1234567890...'
    }
}));
```

#### ICE кандидаты
```javascript
ws.send(JSON.stringify({
    type: 'ice-candidate',
    to: 'targetUserId',
    data: {
        candidate: 'candidate:1 1 UDP 2113667326...',
        sdpMLineIndex: 0
    }
}));
```

#### Разрыв соединения
```javascript
ws.send(JSON.stringify({
    type: 'disconnect',
    to: 'targetUserId',
    data: { reason: 'user_disconnect' }
}));
```

### HTTP API

#### Получение статистики
```bash
GET /api/stats
```

Ответ:
```json
{
    "connectedUsers": 2,
    "users": [
        {
            "id": "uuid-1",
            "state": "connected",
            "targetUser": "uuid-2",
            "connectedAt": "2025-09-09T13:00:00.000Z",
            "ip": "192.168.1.100"
        }
    ]
}
```

## Состояния пользователей

- **idle** - не подключен к другому пользователю
- **connecting** - отправлен ping, ожидается pong
- **connected** - соединение установлено, можно обмениваться offer/answer

## Логирование

Сервер ведет подробные логи всех операций:
- Подключения/отключения пользователей
- Отправка/получение сообщений
- Ошибки и предупреждения
- Статистика соединений

## Безопасность

- Валидация всех входящих сообщений
- Обработка ошибок WebSocket соединений
- Graceful shutdown при получении SIGINT
- Автоматическая очистка отключенных пользователей

## Производительность

- Поддержка множественных одновременных соединений
- Эффективная пересылка сообщений
- Минимальная задержка (real-time)
- Масштабируемая архитектура

## Развертывание

### Локальная разработка
```bash
npm install
npm start
```

### Production
```bash
# Установить PM2 для управления процессами
npm install -g pm2

# Запустить сервер
pm2 start server.js --name "webrtc-signaling"

# Автозапуск при перезагрузке
pm2 startup
pm2 save
```

### Docker
```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
EXPOSE 3000 8080
CMD ["npm", "start"]
```

## Мониторинг

- **Статистика в реальном времени:** http://localhost:3000/api/stats
- **Логи сервера:** выводятся в консоль
- **Состояние соединений:** отслеживается автоматически

## Устранение неполадок

### Проблемы с подключением
1. Проверьте, что сервер запущен на порту 8080
2. Убедитесь, что WebSocket URL правильный
3. Проверьте логи сервера на наличие ошибок

### Проблемы с сообщениями
1. Убедитесь, что формат JSON корректный
2. Проверьте, что все обязательные поля присутствуют
3. Проверьте состояние пользователя перед отправкой

### Производительность
1. Мониторьте количество подключенных пользователей
2. Проверьте использование памяти и CPU
3. При необходимости масштабируйте сервер
