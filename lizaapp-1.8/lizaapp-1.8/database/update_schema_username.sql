-- Обновление схемы базы данных для добавления поля username
USE lizaapp_bd;

-- Добавляем поле username в таблицу users
ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER id;

-- Обновляем статус в таблице contacts для поддержки 'rejected'
ALTER TABLE contacts MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending';

-- Создаем индекс для быстрого поиска по username
CREATE INDEX idx_username ON users(username);
