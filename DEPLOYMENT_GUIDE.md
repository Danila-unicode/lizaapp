# 🚀 Руководство по развертыванию WebRTC приложения

## 📋 Предварительные требования

### Облачные сервисы:
- ✅ **Yandex Cloud Functions** - для HTTP сигналинга
- ✅ **VK Cloud TURN сервер** - для обхода NAT
- ✅ **Google STUN сервер** - для обнаружения NAT

### Локальные требования:
- ✅ **PHP 7.4+** с поддержкой PDO MySQL
- ✅ **MySQL 5.7+** или MariaDB 10.3+
- ✅ **Web сервер** (Apache/Nginx)
- ✅ **HTTPS** (обязательно для WebRTC)

## 🔧 Пошаговое развертывание

### Шаг 1: Настройка базы данных
```bash
# 1. Создайте базу данных
mysql -u root -p
CREATE DATABASE lizaapp_bd;
CREATE USER 'lizaapp_user'@'localhost' IDENTIFIED BY 'aG6lJ9uR5g';
GRANT ALL PRIVILEGES ON lizaapp_bd.* TO 'lizaapp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 2. Импортируйте схему
mysql -u lizaapp_user -p lizaapp_bd < database/schema.sql

# 3. Создайте тестовых пользователей
php setup_database.php
```

### Шаг 2: Проверка облачных сервисов
```bash
# Проверьте подключение к Yandex Cloud Functions и TURN серверу
php test_cloud_connection.php
```

### Шаг 3: Настройка веб-сервера

#### Apache (.htaccess):
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# CORS для API
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type"
```

#### Nginx:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name your-domain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    root /path/to/hosting;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # CORS для API
    add_header Access-Control-Allow-Origin "*" always;
    add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type" always;
}
```

### Шаг 4: Настройка SSL сертификата

#### Let's Encrypt (рекомендуется):
```bash
# Установите Certbot
sudo apt install certbot python3-certbot-apache

# Получите сертификат
sudo certbot --apache -d your-domain.com

# Автоматическое обновление
sudo crontab -e
# Добавьте: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Шаг 5: Проверка развертывания
```bash
# 1. Тест базы данных
php setup_database.php

# 2. Тест облачных сервисов
php test_cloud_connection.php

# 3. Тест всего приложения
php test_app.php
```

## 🧪 Тестирование после развертывания

### 1. Тест регистрации и входа
1. Откройте `https://your-domain.com/register.php`
2. Зарегистрируйте нового пользователя
3. Войдите в систему через `login.php`

### 2. Тест WebRTC звонков
1. Откройте `https://your-domain.com/webrtc-demo-cloud.html`
2. Используйте ID пользователей: `user1` и `user2`
3. Используйте комнату: `room1`
4. Протестируйте видеозвонок

### 3. Тест основного приложения
1. Откройте `https://your-domain.com/index.php`
2. Войдите как пользователь 1
3. Найдите пользователя 2 по номеру телефона
4. Отправьте приглашение
5. Войдите как пользователь 2 и примите приглашение
6. Инициируйте звонок

## 🔍 Диагностика проблем

### Проблемы с базой данных:
```bash
# Проверьте подключение
mysql -u lizaapp_user -p lizaapp_bd -e "SELECT COUNT(*) FROM users;"

# Проверьте права доступа
ls -la config/database.php
```

### Проблемы с WebRTC:
1. Проверьте консоль браузера на ошибки
2. Убедитесь, что сайт работает по HTTPS
3. Проверьте доступность камеры/микрофона
4. Запустите `test_cloud_connection.php`

### Проблемы с облачными сервисами:
```bash
# Проверьте Yandex Cloud Functions
curl -X GET "https://functions.yandexcloud.net/d4ec0rusp5blvc9pucd4?action=status"

# Проверьте TURN сервер
telnet 109.120.183.43 3478
```

## 📊 Мониторинг

### Логи приложения:
```bash
# Логи Apache
tail -f /var/log/apache2/error.log

# Логи PHP
tail -f /var/log/php/error.log

# Логи MySQL
tail -f /var/log/mysql/error.log
```

### Мониторинг производительности:
```bash
# Использование CPU и памяти
htop

# Использование диска
df -h

# Сетевые соединения
netstat -tulpn
```

## 🔒 Безопасность

### Рекомендации:
1. **Используйте HTTPS** - обязательно для WebRTC
2. **Обновите пароли** - измените пароли по умолчанию
3. **Настройте файрвол** - ограничьте доступ к портам
4. **Регулярно обновляйте** - PHP, MySQL, веб-сервер
5. **Мониторьте логи** - следите за подозрительной активностью

### Настройка файрвола:
```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# iptables
iptables -A INPUT -p tcp --dport 22 -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -j DROP
```

## 📈 Масштабирование

### Для высоких нагрузок:
1. **Redis** - для хранения сигналов вместо файлов
2. **Load Balancer** - для распределения нагрузки
3. **CDN** - для статических ресурсов
4. **Database Replication** - для отказоустойчивости

### Настройка Redis:
```bash
# Установка Redis
sudo apt install redis-server

# Настройка в PHP
sudo apt install php-redis

# Обновление кода для использования Redis
# (требует модификации signaling_server.php)
```

## 🎯 Финальная проверка

### Чек-лист развертывания:
- [ ] База данных создана и настроена
- [ ] Тестовые пользователи созданы
- [ ] HTTPS сертификат установлен
- [ ] Yandex Cloud Functions доступен
- [ ] TURN сервер VK Cloud доступен
- [ ] WebRTC звонки работают
- [ ] Регистрация/вход работают
- [ ] Система контактов работает
- [ ] Логирование настроено
- [ ] Мониторинг настроен

---

**Приложение готово к продакшену!** 🚀

После выполнения всех шагов ваше WebRTC приложение будет полностью функциональным и готовым к использованию.
