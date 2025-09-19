const WebSocket = require('ws');
const express = require('express');
const cors = require('cors');
const { v4: uuidv4 } = require('uuid');
const path = require('path');
const https = require('https');
const http = require('http');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;
const WSS_PORT = process.env.WSS_PORT || 8080;

// Middleware
app.use(cors({
    origin: [
        'https://lizaapp.wg01.ru',
        'https://lizamsg.ru',
        'https://89.169.141.202:3000',
        'http://localhost:3000',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080'
    ],
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With']
}));
app.use(express.json());
app.use(express.static('public'));

// SSL сертификаты
let httpsOptions = null;
try {
    httpsOptions = {
        key: fs.readFileSync('/home/ubuntu/ssl/key.pem'),
        cert: fs.readFileSync('/home/ubuntu/ssl/cert.pem')
    };
    console.log('✅ SSL сертификаты загружены');
} catch (error) {
    console.log('⚠️ SSL сертификаты не найдены, используем HTTP');
}

// HTTPS сервер
let httpsServer = null;
if (httpsOptions) {
    httpsServer = https.createServer(httpsOptions, app);
}

// WebSocket сервер (HTTPS или HTTP)
const wss = httpsOptions ?
    new WebSocket.Server({ server: httpsServer }) :
    new WebSocket.Server({ port: WSS_PORT });

// Функция логирования
function log(message, type = 'info') {
    const timestamp = new Date().toISOString();
    const prefix = type === 'error' ? '❌' : type === 'warning' ? '⚠️' : type === 'success' ? '✅' : '📝';
    console.log(`[${timestamp}] ${prefix} ${message}`);
}

// Хранилище подключенных пользователей
const connectedUsers = new Map();

// Обработка WebSocket соединений
wss.on('connection', (ws, req) => {
    const userId = uuidv4();
    const clientIP = req.socket.remoteAddress;

    log(`Новое WebSocket соединение: ${userId} (IP: ${clientIP})`, 'success');

    // Сохраняем информацию о пользователе
    connectedUsers.set(userId, {
        ws: ws,
        id: userId,
        state: 'idle',
        targetUser: null,
        ip: clientIP,
        connectedAt: new Date()
    });

    // Отправляем приветственное сообщение
    ws.send(JSON.stringify({
        type: 'connected',
        userId: userId,
        message: 'Подключение к серверу установлено'
    }));

    // Обработка сообщений
    ws.on('message', (data) => {
        try {
            const message = JSON.parse(data);
            handleMessage(userId, message);
        } catch (error) {
            log(`Ошибка парсинга сообщения от ${userId}: ${error.message}`, 'error');
            ws.send(JSON.stringify({
                type: 'error',
                message: 'Неверный формат сообщения'
            }));
        }
    });

    // Обработка закрытия соединения
    ws.on('close', () => {
        log(`WebSocket соединение закрыто: ${userId}`, 'warning');
        handleDisconnect(userId);
    });

    // Обработка ошибок
    ws.on('error', (error) => {
        log(`Ошибка WebSocket для ${userId}: ${error.message}`, 'error');
        handleDisconnect(userId);
    });
});

// Обработка сообщений
function handleMessage(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) {
        log(`Пользователь ${userId} не найден`, 'error');
        return;
    }

    log(`Получено сообщение от ${userId}: ${message.type}`, 'info');

    switch (message.type) {
        case 'ping':
            handlePing(userId, message);
            break;
        case 'pong':
            handlePong(userId, message);
            break;
        case 'offer':
            handleOffer(userId, message);
            break;
        case 'answer':
            handleAnswer(userId, message);
            break;
        case 'ice-candidate':
            handleIceCandidate(userId, message);
            break;
        case 'disconnect':
            handleDisconnectSignal(userId, message);
            break;
        default:
            user.ws.send(JSON.stringify({
                type: 'error',
                message: `Неизвестный тип сообщения: ${message.type}`
            }));
    }
}

// Обработка ping
function handlePing(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;

    if (user.state !== 'idle') {
        log(`Игнорируем ping от ${userId} - состояние: ${user.state}`, 'warning');
        return;
    }

    user.state = 'connecting';
    user.targetUser = message.to;

    log(`Ping от ${userId} к ${message.to}`, 'info');

    // Пересылаем ping целевому пользователю
    forwardMessage(message.to, {
        type: 'ping',
        from: userId,
        to: message.to,
        data: message.data
    });
}

// Обработка pong
function handlePong(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;

    log(`Pong от ${userId} к ${message.to}`, 'info');

    // Пересылаем pong целевому пользователю
    forwardMessage(message.to, {
        type: 'pong',
        from: userId,
        to: message.to,
        data: message.data
    });

    // Обновляем состояние обоих пользователей
    const targetUser = connectedUsers.get(message.to);
    if (targetUser) {
        targetUser.state = 'connected';
        targetUser.targetUser = userId;
        user.state = 'connected';

        log(`Соединение установлено между ${userId} и ${message.to}`, 'success');
    }
}

// Обработка offer
function handleOffer(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;

    if (user.state !== 'connected') {
        log(`Игнорируем offer от ${userId} - состояние: ${user.state}`, 'warning');
        return;
    }

    log(`Offer от ${userId} к ${message.to}`, 'info');

    // Пересылаем offer целевому пользователю
    forwardMessage(message.to, {
        type: 'offer',
        from: userId,
        to: message.to,
        data: message.data
    });
}

// Обработка answer
function handleAnswer(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;

    if (user.state !== 'connected') {
        log(`Игнорируем answer от ${userId} - состояние: ${user.state}`, 'warning');
        return;
    }

    log(`Answer от ${userId} к ${message.to}`, 'info');

    // Пересылаем answer целевому пользователю
    forwardMessage(message.to, {
        type: 'answer',
        from: userId,
        to: message.to,
        data: message.data
    });
}

// Обработка ICE кандидатов
function handleIceCandidate(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;

    log(`ICE кандидат от ${userId} к ${message.to}`, 'info');

    // Пересылаем ICE кандидат целевому пользователю
    forwardMessage(message.to, {
        type: 'ice-candidate',
        from: userId,
        to: message.to,
        data: message.data
    });
}

// Обработка сигнала disconnect
function handleDisconnectSignal(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;

    log(`Сигнал disconnect от ${userId} к ${message.to}`, 'warning');

    // Пересылаем disconnect целевому пользователю
    forwardMessage(message.to, {
        type: 'disconnect',
        from: userId,
        to: message.to,
        data: message.data
    });

    // Сбрасываем состояние пользователя
    user.state = 'idle';
    user.targetUser = null;
}

// Пересылка сообщения
function forwardMessage(targetUserId, message) {
    const targetUser = connectedUsers.get(targetUserId);
    if (!targetUser) {
        log(`Целевой пользователь ${targetUserId} не найден`, 'warning');
        return;
    }

    if (targetUser.ws.readyState === WebSocket.OPEN) {
        targetUser.ws.send(JSON.stringify(message));
        log(`Сообщение ${message.type} переслано от ${message.from} к ${targetUserId}`, 'success');
    } else {
        log(`WebSocket для пользователя ${targetUserId} не открыт`, 'warning');
    }
}

// Обработка отключения пользователя
function handleDisconnect(userId) {
    const user = connectedUsers.get(userId);
    if (!user) return;
    
    // Уведомляем целевого пользователя об отключении
    if (user.targetUser) {
        const targetUser = connectedUsers.get(user.targetUser);
        if (targetUser && targetUser.ws.readyState === WebSocket.OPEN) {
            targetUser.ws.send(JSON.stringify({
                type: 'disconnect',
                from: userId,
                to: user.targetUser,
                data: { reason: 'user_disconnected' }
            }));

            // Сбрасываем состояние целевого пользователя
            targetUser.state = 'idle';
            targetUser.targetUser = null;
        }
    }

    // Удаляем пользователя из хранилища
    connectedUsers.delete(userId);
    log(`Пользователь ${userId} удален из системы`, 'info');
}

// HTTP API для получения статистики
app.get('/api/stats', (req, res) => {
    const stats = {
        connectedUsers: connectedUsers.size,
        users: Array.from(connectedUsers.values()).map(user => ({
            id: user.id,
            state: user.state,
            targetUser: user.targetUser,
            connectedAt: user.connectedAt,
            ip: user.ip
        }))
    };
    res.json(stats);
});

// Запуск сервера
if (httpsOptions && httpsServer) {
    // HTTPS режим
    httpsServer.listen(PORT, () => {
        log(`HTTPS сервер запущен на порту ${PORT}`, 'success');
        log(`WSS сервер запущен на порту ${WSS_PORT}`, 'success');
        log(`Статистика доступна по адресу: https://lizamsg.ru:${PORT}/api/stats`, 'info');
        log(`WebSocket доступен по адресу: wss://lizamsg.ru:${WSS_PORT}`, 'info');
    });
} else {
    // HTTP режим (fallback)
    app.listen(PORT, () => {
        log(`HTTP сервер запущен на порту ${PORT}`, 'success');
        log(`WebSocket сервер запущен на порту ${WSS_PORT}`, 'success');
        log(`Статистика доступна по адресу: http://localhost:${PORT}/api/stats`, 'info');
    });
}

// Graceful shutdown
process.on('SIGTERM', () => {
    log('Получен сигнал SIGTERM, завершаем работу...', 'warning');
    process.exit(0);
});

process.on('SIGINT', () => {
    log('Получен сигнал SIGINT, завершаем работу...', 'warning');
    process.exit(0);
});
