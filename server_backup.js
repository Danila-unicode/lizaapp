const WebSocket = require('ws');
const express = require('express');
const cors = require('cors');
const { v4: uuidv4 } = require('uuid');
const path = require('path');
const https = require('https');
const http = require('http');
const fs = require('fs');
const mysql = require('mysql2/promise');
const bcrypt = require('bcrypt');

const app = express();
const PORT = process.env.PORT || 3000;
const WSS_PORT = process.env.WSS_PORT || 8080;

// MySQL подключение
const dbConfig = {
    host: 'lizaapp.wg01.ru',
    user: 'lizaapp_q2f112f1c',
    password: 'mS2rJ7uK5r',
    database: 'lizaapp_fgdg1c1d551v1d',
    port: 3306
};

let db;

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

// Функция логирования
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

// Хранилище подключенных пользователей
const connectedUsers = new Map();

// Хранилище сигналов для HTTP API
const signals = new Map(); // userId -> [signals]
const userRooms = new Map();

// Функция инициализации базы данных
async function initDatabase() {
    try {
        db = await mysql.createConnection(dbConfig);
        console.log('✅ Подключение к MySQL установлено');
        
        // Создаем таблицы если их нет
        await db.execute(`
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(15) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);
        
        await db.execute(`
            CREATE TABLE IF NOT EXISTS active_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        `);
        
        // Создаем индексы
        await db.execute(`CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone)`);
        await db.execute(`CREATE INDEX IF NOT EXISTS idx_active_sessions_user_id ON active_sessions(user_id)`);
        await db.execute(`CREATE INDEX IF NOT EXISTS idx_active_sessions_token ON active_sessions(token)`);
        
        console.log('✅ База данных инициализирована');
    } catch (error) {
        console.error('❌ Ошибка подключения к базе данных:', error);
        process.exit(1);
    }
}

// Функции для работы с пользователями
async function registerUser(phone, password) {
    try {
        const hashedPassword = await bcrypt.hash(password, 10);
        const [result] = await db.execute(
            'INSERT INTO users (phone, password_hash) VALUES (?, ?)',
            [phone, hashedPassword]
        );
        return { success: true, userId: result.insertId };
    } catch (error) {
        if (error.code === 'ER_DUP_ENTRY') {
            return { success: false, error: 'Пользователь уже существует' };
        }
        return { success: false, error: 'Ошибка регистрации' };
    }
}

async function loginUser(phone, password) {
    try {
        const [rows] = await db.execute(
            'SELECT id, password_hash FROM users WHERE phone = ?',
            [phone]
        );
        
        if (rows.length === 0) {
            return { success: false, error: 'Пользователь не найден' };
        }
        
        const user = rows[0];
        const isValidPassword = await bcrypt.compare(password, user.password_hash);
        
        if (!isValidPassword) {
            return { success: false, error: 'Неверный пароль' };
        }
        
        // Создаем сессию
        const sessionToken = uuidv4();
        const expiresAt = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 часа
        
        await db.execute(
            'INSERT INTO active_sessions (user_id, token, expires_at) VALUES (?, ?, ?)',
            [user.id, sessionToken, expiresAt]
        );
        
        return { 
            success: true, 
            userId: user.id, 
            sessionToken: sessionToken 
        };
    } catch (error) {
        return { success: false, error: 'Ошибка авторизации' };
    }
}

async function validateSession(sessionToken) {
    try {
        const [rows] = await db.execute(
            'SELECT u.id, u.phone FROM users u JOIN active_sessions s ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW()',
            [sessionToken]
        );
        
        if (rows.length === 0) {
            return { success: false, error: 'Недействительная сессия' };
        }
        
        return { success: true, user: rows[0] };
    } catch (error) {
        return { success: false, error: 'Ошибка валидации сессии' };
    }
}

async function logoutUser(sessionToken) {
    try {
        await db.execute('DELETE FROM active_sessions WHERE token = ?', [sessionToken]);
        return { success: true };
    } catch (error) {
        return { success: false, error: 'Ошибка выхода' };
    }
}

async function getAllUsers() {
    try {
        const [rows] = await db.execute('SELECT id, phone, created_at FROM users ORDER BY created_at DESC');
        return rows;
    } catch (error) {
        console.error('Ошибка получения пользователей:', error);
        return [];
    }
}

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
        connectedUsers.delete(userId);
    });
    
    // Обработка ошибок
    ws.on('error', (error) => {
        log(`Ошибка WebSocket для ${userId}: ${error.message}`, 'error');
        connectedUsers.delete(userId);
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
            handleDisconnect(userId, message);
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
    
    if (user.state !== 'connecting') {
        log(`Игнорируем pong от ${userId} - состояние: ${user.state}`, 'warning');
        return;
    }
    
    user.state = 'connected';
    user.targetUser = message.to;
    
    log(`Pong от ${userId} к ${message.to}`, 'info');
    
    // Пересылаем pong целевому пользователю
    forwardMessage(message.to, {
        type: 'pong',
        from: userId,
        to: message.to,
        data: message.data
    });
}

// Обработка offer
function handleOffer(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;
    
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
    
    log(`Answer от ${userId} к ${message.to}`, 'info');
    
    // Пересылаем answer целевому пользователю
    forwardMessage(message.to, {
        type: 'answer',
        from: userId,
        to: message.to,
        data: message.data
    });
}

// Обработка ICE candidate
function handleIceCandidate(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;
    
    log(`ICE candidate от ${userId} к ${message.to}`, 'info');
    
    // Пересылаем ICE candidate целевому пользователю
    forwardMessage(message.to, {
        type: 'ice-candidate',
        from: userId,
        to: message.to,
        data: message.data
    });
}

// Обработка disconnect
function handleDisconnect(userId, message) {
    const user = connectedUsers.get(userId);
    if (!user) return;
    
    log(`Disconnect от ${userId}`, 'info');
    
    // Сбрасываем состояние
    user.state = 'idle';
    user.targetUser = null;
    
    // Уведомляем целевого пользователя
    if (message.to) {
        forwardMessage(message.to, {
            type: 'disconnect',
            from: userId,
            to: message.to,
            data: message.data
        });
    }
}

// Пересылка сообщения
function forwardMessage(targetUserId, message) {
    const targetUser = connectedUsers.get(targetUserId);
    if (!targetUser) {
        log(`Целевой пользователь ${targetUserId} не найден`, 'warning');
        return;
    }
    
    try {
        targetUser.ws.send(JSON.stringify(message));
        log(`Сообщение переслано к ${targetUserId}`, 'info');
    } catch (error) {
        log(`Ошибка пересылки сообщения к ${targetUserId}: ${error.message}`, 'error');
    }
}

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
        // Получение списка пользователей из MySQL
        getAllUsers().then(users => {
            res.json({
                success: true,
                users: users.map(user => user.id)
            });
        }).catch(error => {
            res.status(500).json({ success: false, error: 'Ошибка получения пользователей' });
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
            // Регистрируем пользователя
            const { login, password } = data;
            if (!login || !password) {
                return res.status(400).json({ success: false, error: 'Логин и пароль обязательны' });
            }
            
            registerUser(login, password).then(result => {
                if (result.success) {
                    res.json({ success: true, message: 'Пользователь зарегистрирован', userId: result.userId });
                } else {
                    res.status(400).json({ success: false, error: result.error });
                }
            }).catch(error => {
                res.status(500).json({ success: false, error: 'Ошибка регистрации пользователя' });
            });
            break;
            
        case 'login':
            // Авторизация пользователя
            const { login: loginData, password: passwordData } = data;
            if (!loginData || !passwordData) {
                return res.status(400).json({ success: false, error: 'Логин и пароль обязательны' });
            }
            
            loginUser(loginData, passwordData).then(result => {
                if (result.success) {
                    res.json({ 
                        success: true, 
                        message: 'Авторизация успешна', 
                        userId: result.userId, 
                        sessionToken: result.sessionToken 
                    });
                } else {
                    res.status(401).json({ success: false, error: result.error });
                }
            }).catch(error => {
                res.status(500).json({ success: false, error: 'Ошибка авторизации' });
            });
            break;
            
        case 'ping':
            // Сохраняем ping сигнал
            saveSignal(from, to, 'ping', data);
            
            // Автоматически отправляем pong обратно
            saveSignal(to, from, 'pong', { 
                timestamp: Date.now(),
                originalPing: data 
            });
            
            log(`Ping от ${from} к ${to} - автоматически отправлен pong`, 'info');
            res.json({ success: true, message: 'Ping обработан, pong отправлен' });
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

// API для управления пользователями
app.get('/api/users', async (req, res) => {
    try {
        const users = await getAllUsers();
        res.json({
            success: true,
            users: users
        });
    } catch (error) {
        res.status(500).json({ success: false, error: 'Ошибка получения пользователей' });
    }
});

// API для регистрации
app.post('/api/register', async (req, res) => {
    try {
        const { login, password } = req.body;
        if (!login || !password) {
            return res.status(400).json({ success: false, error: 'Логин и пароль обязательны' });
        }
        
        const result = await registerUser(login, password);
        if (result.success) {
            res.json({ success: true, message: 'Пользователь зарегистрирован', userId: result.userId });
        } else {
            res.status(400).json({ success: false, error: result.error });
        }
    } catch (error) {
        res.status(500).json({ success: false, error: 'Ошибка регистрации' });
    }
});

// API для авторизации
app.post('/api/login', async (req, res) => {
    try {
        const { login, password } = req.body;
        if (!login || !password) {
            return res.status(400).json({ success: false, error: 'Логин и пароль обязательны' });
        }
        
        const result = await loginUser(login, password);
        if (result.success) {
            res.json({ 
                success: true, 
                message: 'Авторизация успешна', 
                userId: result.userId, 
                sessionToken: result.sessionToken 
            });
        } else {
            res.status(401).json({ success: false, error: result.error });
        }
    } catch (error) {
        res.status(500).json({ success: false, error: 'Ошибка авторизации' });
    }
});

// API для выхода
app.post('/api/logout', async (req, res) => {
    try {
        const { sessionToken } = req.body;
        if (!sessionToken) {
            return res.status(400).json({ success: false, error: 'Токен сессии обязателен' });
        }
        
        const result = await logoutUser(sessionToken);
        if (result.success) {
            res.json({ success: true, message: 'Выход выполнен' });
        } else {
            res.status(400).json({ success: false, error: result.error });
        }
    } catch (error) {
        res.status(500).json({ success: false, error: 'Ошибка выхода' });
    }
});

// API для проверки сессии
app.get('/api/validate', async (req, res) => {
    try {
        const { sessionToken } = req.query;
        if (!sessionToken) {
            return res.status(400).json({ success: false, error: 'Токен сессии обязателен' });
        }
        
        const result = await validateSession(sessionToken);
        if (result.success) {
            res.json({ success: true, user: result.user });
        } else {
            res.status(401).json({ success: false, error: result.error });
        }
    } catch (error) {
        res.status(500).json({ success: false, error: 'Ошибка валидации' });
    }
});

// Создание WebSocket сервера
const wss = new WebSocket.Server({ 
    port: WSS_PORT,
    verifyClient: (info) => {
        // Разрешаем все соединения
        return true;
    }
});

// Запуск сервера
async function startServer() {
    await initDatabase();
    
    if (httpsOptions) {
        const httpsServer = https.createServer(httpsOptions, app);
        httpsServer.listen(PORT, () => {
            log(`HTTPS сервер запущен на порту ${PORT}`, 'success');
        });
    } else {
        app.listen(PORT, () => {
            log(`HTTP сервер запущен на порту ${PORT}`, 'success');
        });
    }
    
    log(`WebSocket сервер запущен на порту ${WSS_PORT}`, 'success');
}

startServer().catch(error => {
    log(`Ошибка запуска сервера: ${error.message}`, 'error');
    process.exit(1);
});

