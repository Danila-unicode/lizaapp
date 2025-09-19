# Развертывание системы друзей

## Обновленные файлы API

Все файлы в папке `api/` обновлены для работы с логинами вместо телефонов:

### Основные изменения:
- `search_user.php` - поиск по логину через GET параметр
- `get_contacts.php` - возвращает username вместо phone
- `send_invitation.php` - принимает target_username вместо phone
- `get_requests.php` - возвращает username вместо phone
- `get_sent_requests.php` - новый файл для отправленных запросов
- `accept_invitation.php` - принимает sender_username вместо contact_id
- `reject_invitation.php` - принимает sender_username вместо contact_id

## Обновление базы данных

### 1. Выполните SQL скрипт:
```sql
-- Добавляем поле username в таблицу users
ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER id;

-- Обновляем статус в таблице contacts для поддержки 'rejected'
ALTER TABLE contacts MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending';

-- Создаем индекс для быстрого поиска по username
CREATE INDEX idx_username ON users(username);
```

### 2. Обновите существующих пользователей:
```sql
-- Установите username для существующих пользователей
UPDATE users SET username = CONCAT('user', id) WHERE username IS NULL;
```

## Загрузка на сервер

### 1. Загрузите обновленные файлы API:
- `api/search_user.php`
- `api/get_contacts.php`
- `api/send_invitation.php`
- `api/get_requests.php`
- `api/get_sent_requests.php` (новый)
- `api/accept_invitation.php`
- `api/reject_invitation.php`

### 2. Выполните SQL скрипт на сервере:
```bash
mysql -u lizaapp_user -p lizaapp_bd < database/update_schema_username.sql
```

### 3. Обновите существующих пользователей:
```sql
UPDATE users SET username = CONCAT('user', id) WHERE username IS NULL;
```

## Тестирование

### 1. Проверьте поиск пользователей:
```
GET https://lizaapp.wg01.ru/api/search_user.php?username=user2
```

### 2. Проверьте отправку запроса в друзья:
```javascript
fetch('https://lizaapp.wg01.ru/api/send_invitation.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ target_username: 'user2' })
});
```

### 3. Проверьте получение друзей:
```
GET https://lizaapp.wg01.ru/api/get_contacts.php
```

## Интеграция с клиентом

Система друзей уже интегрирована в `simple-signal-test-websocket.html`:

- ✅ Поиск пользователей
- ✅ Отправка запросов в друзья
- ✅ Принятие/отклонение запросов
- ✅ Список друзей с видеозвонками
- ✅ Интеграция с существующим функционалом звонков

## Примечания

- Все API endpoints используют существующую авторизацию через сессии
- Старые кнопки звонков скрыты, но функционал сохранен
- Система друзей использует существующую базу данных
- WebSocket сервер остается на порту 9000
- HTTP API остается на порту 8080
