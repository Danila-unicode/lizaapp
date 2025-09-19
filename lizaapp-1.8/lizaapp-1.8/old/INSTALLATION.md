# Установка системы видеозвонков с MySQL

## 1. Настройка MySQL

Выполните SQL скрипт `mysql_config.sql` на вашем MySQL сервере:

```bash
mysql -u root -p < mysql_config.sql
```

Или выполните команды вручную:

```sql
CREATE DATABASE lizaapp_fgdg1c1d551v1d CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lizaapp_q2f112f1c'@'%' IDENTIFIED BY 'mS2rJ7uK5r';
GRANT ALL PRIVILEGES ON lizaapp_fgdg1c1d551v1d.* TO 'lizaapp_q2f112f1c'@'%';
FLUSH PRIVILEGES;
```

## 2. Установка зависимостей

```bash
cd websocket-server
npm install
```

## 3. Запуск сервера

```bash
node server.js
```

Сервер автоматически создаст таблицы при первом запуске.

## 4. Использование системы

### Регистрация:
1. Откройте `simple-signal-test-websocket.html` в браузере
2. Введите логин и пароль в форме регистрации
3. Нажмите "Зарегистрироваться"

### Авторизация:
1. Введите ваш логин и пароль в форме авторизации
2. Нажмите "Войти"
3. Система покажет список всех зарегистрированных пользователей

### Звонок:
1. Выберите пользователя из списка
2. Нажмите "Позвонить"
3. Если пользователь в сети - установится P2P соединение
4. Если пользователь не в сети - появится сообщение "Пользователь не в сети"

## 5. API Endpoints

- `POST /api/register` - регистрация пользователя
- `POST /api/login` - авторизация пользователя
- `POST /api/logout` - выход из системы
- `GET /api/users` - получить всех пользователей
- `GET /api/signaling?action=getUsers` - совместимость со старым API

## 6. Структура базы данных

### Таблица `users`:
- `id` - автоинкрементный ID
- `login` - логин пользователя (уникальный)
- `password` - хешированный пароль
- `created_at` - дата создания
- `updated_at` - дата обновления

### Таблица `active_sessions`:
- `user_id` - ID пользователя
- `session_token` - токен сессии
- `created_at` - дата создания сессии
