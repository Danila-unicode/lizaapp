# Настройка MySQL для системы видеозвонков

## 1. Создание базы данных

Подключитесь к MySQL на хостинге `lizaapp.wg01.ru` и выполните:

```sql
CREATE DATABASE lizaapp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 2. Создание пользователя

```sql
CREATE USER 'lizaapp_user'@'%' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON lizaapp_db.* TO 'lizaapp_user'@'%';
FLUSH PRIVILEGES;
```

## 3. Обновление конфигурации

В файле `websocket-server/server.js` обновите данные подключения:

```javascript
const dbConfig = {
    host: 'lizaapp.wg01.ru',
    user: 'lizaapp_user',
    password: 'your_secure_password', // Замените на реальный пароль
    database: 'lizaapp_db',
    port: 3306
};
```

## 4. Установка зависимостей

```bash
cd websocket-server
npm install mysql2
```

## 5. Запуск сервера

```bash
node server.js
```

Сервер автоматически создаст таблицу `users` при первом запуске.

## 6. Структура таблицы

```sql
CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 7. API Endpoints

- `GET /api/users` - получить всех пользователей
- `POST /api/users` - добавить/обновить пользователя
- `DELETE /api/users/:id` - удалить пользователя
- `GET /api/signaling?action=getUsers` - получить ID пользователей (для совместимости)
