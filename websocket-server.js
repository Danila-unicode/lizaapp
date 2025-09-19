// Обработка необработанных ошибок\nprocess.on( uncaughtException, (error) => {\n    log(Необработанная ошибка: , error);\n    log(Стек: , error);\n});\n\nprocess.on(unhandledRejection, (reason, promise) => {\n    log(Необработанное отклонение: , error);\n});\n
const WebSocket = require('ws');
const express = require('express');
const cors = require('cors');
const { v4: uuidv4 } = require('uuid');
const path = require('path');
const https = require('https');
const http = require('http');
const fs = require('fs');
// Удаляем mysql и bcrypt - авторизация через PHP API

const app = express();
const PORT = process.env.PORT || 9000;
const WSS_PORT = process.env.WSS_PORT || 9000;

// База данных не нужна - авторизация через PHP API

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
    console.log('⚠️ SSL сертификаты не найдены, запускаем без HTTPS');
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

// Хранилище подключенных пользователей
const connectedUsers = new Map();

// Хранилище сигналов для HTTP API
const signals = new Map(); // userId -> [signals]
const userRooms = new Map();

// Инициализация базы данных не нужна - авторизация через PHP API

// Логирование
function log(message, type = 'info') {
    const timestamp = new Date().toISOString();
    const prefix = type === 'error' ? '❌' : type === 'warning' ? '⚠️' : type === 'success' ? '✅' : '📝';
    console.log(`[${timestamp}] ${prefix} ${message}`);
}

// Функция для сохранения сигнала
function saveSignal(from, to, type, data) {
    const signal = {
        id: Date.now(),
        timestamp: Math.floor(Date.now() / 1000),
        from,
        to,
        type,
        data
    };
    
    // Сохраняем сигнал для получателя
    if (!signals.has(to)) {
        signals.set(to, []);
    }
    signals.get(to).push(signal);
    
    // Ограничиваем количество сигналов (последние 100)
    const userSignals = signals.get(to);
    if (userSignals.length > 100) {
        userSignals.splice(0, userSignals.length - 100);
    }
    
    log(`Сигнал сохранен: ${type} от ${from} к ${to}`, 'info');
}

// Функции регистрации/авторизации не нужны - используются через PHP API

// Обработка WebSocket соединений
wss.on('connection', (ws, req) => {
    const url = new URL(req.url, `http://${req.headers.host}`);
    const username = url.searchParams.get("username");
    if (!username) {
        ws.close(1008, "Username required");
        return;
    }
    const userId = username;
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
        username: userId,
        username: userId,
        userId: userId,
        message: 'Подключение к серверу установлено'
    }));
    
    // Обработка входящих сообщений
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
    
    // Обработка отключения
    ws.on('close', () => {
        log(`Пользователь ${userId} отключился`, 'warning');
        handleDisconnect(userId);
    });
    
    // Обработка ошибок
    ws.on('error', (error) => {
        log(`Ошибка WebSocket для пользователя ${userId}: ${error.message}`, 'error');
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
        case 'call_rejected':
            handleCallRejected(userId, message);
            break;
        default:
            log(`Неизвестный тип сообщения: ${message.type}`, 'warning');
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

// Обработка сигнала call_rejected
function handleCallRejected(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;
    
    log(`Сигнал call_rejected от ${userId} к ${message.to}`, 'warning');
    
    // Пересылаем call_rejected целевому пользователю
    forwardMessage(message.to, {
        type: 'call_rejected',
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

// HTTP API для получения сигналов
app.get('/api/signaling', (req, res) => {
    const { action, userId, since } = req.query;
    
    if (action === 'signals' && userId) {
        const userSignals = signals.get(userId) || [];
        const sinceTimestamp = parseInt(since) || 0;
        
        // Фильтруем сигналы по времени
        const filteredSignals = userSignals.filter(signal => 
            signal.timestamp > sinceTimestamp
        );
        
        log(`Запрос сигналов для ${userId}: найдено ${filteredSignals.length} из ${userSignals.length}`, 'info');
        
        res.json({
            success: true,
            signals: filteredSignals,
            total: userSignals.length,
            new: filteredSignals.length
        });
    } else if (action === 'delete' && userId) {
        // Удаляем все сигналы для пользователя
        signals.delete(userId);
        log(`Все сигналы удалены для пользователя ${userId}`, 'info');
        res.json({ success: true, message: 'Сигналы удалены' });
    } else if (action === 'getUsers') {
        // Получение списка пользователей не поддерживается - используется PHP API
        res.json({
            success: false,
            error: 'Получение пользователей через WebSocket API не поддерживается'
        });
    } else {
        res.json({ success: false, message: 'Неверные параметры запроса' });
    }
});

// HTTP API для сигналинга (совместимость с Yandex Cloud Functions)
app.post('/api/signaling', (req, res) => {
    const { action, from, to, type, data } = req.body;
    
    log(`HTTP сигналинг: ${action} от ${from} к ${to}`, 'info');
    
    switch (action) {
        case 'register':
            // Регистрация не поддерживается - используется PHP API
            res.status(400).json({ 
                success: false, 
                error: 'Регистрация через WebSocket API не поддерживается. Используйте PHP API.' 
            });
            break;
            
        case 'login':
            // Авторизация не поддерживается - используется PHP API
            res.status(400).json({ 
                success: false, 
                error: 'Авторизация через WebSocket API не поддерживается. Используйте PHP API.' 
            });
            break;
            
        case 'ping':
            try {
                // Сохраняем ping сигнал
                log(`Сохраняем ping от ${from} к ${to}`, 'info');
                saveSignal(from, to, 'ping', data);
                
                // Автоматически отправляем pong обратно
                log(`Сохраняем pong от ${to} к ${from}`, 'info');
                saveSignal(to, from, 'pong', { 
                    timestamp: Date.now(),
                    originalPing: data 
                });
                
                log(`Ping от ${from} к ${to} - автоматически отправлен pong`, 'info');
                res.json({ success: true, message: 'Ping обработан, pong отправлен' });
            } catch (error) {
                log(`Ошибка при обработке ping: ${error.message}`, 'error');
                res.json({ success: false, message: 'Ошибка обработки ping' });
            }
            break;
            
        case 'signal':
            // Сохраняем сигнал
            saveSignal(from, to, type, data);
            res.json({ success: true, message: 'Сигнал обработан' });
            break;
            
        default:
            res.json({ success: false, message: 'Неизвестное действие' });
    }
});

// HTTP API для регистрации/авторизации не нужен - используется PHP API

// Запуск сервера
function startServer() {
    if (httpsOptions && httpsServer) {
        // HTTPS режим
        httpsServer.listen(PORT, "0.0.0.0", () => {
            log(`HTTPS сервер запущен на порту ${PORT}`, 'success');
            log(`WSS сервер запущен на порту ${WSS_PORT}`, 'success');
            log(`Статистика доступна по адресу: https://lizamsg.ru:${PORT}/api/stats`, 'info');
            log(`WebSocket доступен по адресу: wss://lizamsg.ru:${WSS_PORT}`, 'info');
        });
    } else {
        // HTTP режим (fallback)
        app.listen(PORT, "0.0.0.0", () => {
            log(`HTTP сервер запущен на порту ${PORT}`, 'success');
            log(`WebSocket сервер запущен на порту ${WSS_PORT}`, 'success');
            log(`Статистика доступна по адресу: http://localhost:${PORT}/api/stats`, 'info');
        });
    }
}

startServer();

// Graceful shutdown
process.on('SIGINT', () => {
    log('Получен сигнал SIGINT, завершаем работу...', 'warning');
    
    // Закрываем все WebSocket соединения
    connectedUsers.forEach((user) => {
        if (user.ws.readyState === WebSocket.OPEN) {
            user.ws.close();
        }
    });
    
    wss.close(() => {
        log('WebSocket сервер остановлен', 'info');
        if (httpsServer) {
            httpsServer.close(() => {
                log('HTTPS сервер остановлен', 'info');
                process.exit(0);
            });
        } else {
            process.exit(0);
        }
    });
});
