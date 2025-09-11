# 📦 Инструкция по загрузке на lizamsg.ru

## 🎯 **Архив готов:** `lizamsg-upload.zip`

### 📁 **Содержимое архива:**
- `simple-signal-test-websocket.html` - **НОВЫЙ** тест WebSocket сигналинга
- `webrtc-test-simple.html` - Полный тест всех стадий WebRTC
- `contacts-app.html` - Основное приложение контактов
- `contacts-app3.html` - Улучшенная версия контактов
- `contacts-app-improved.html` - Дополнительно улучшенная версия
- `assets/` - Папка с JavaScript файлами

## 🚀 **Инструкция по загрузке:**

### Шаг 1: Загрузите архив
1. Распакуйте `lizamsg-upload.zip` на ваш хостинг `lizamsg.ru`
2. Убедитесь, что структура папок сохранена:
   ```
   lizamsg.ru/
   ├── simple-signal-test-websocket.html
   ├── webrtc-test-simple.html
   ├── contacts-app.html
   ├── contacts-app3.html
   ├── contacts-app-improved.html
   └── assets/
       ├── js/
       │   ├── webrtc.js
       │   ├── webrtc-http.js
       │   ├── websocket-client.js
       │   └── websocket-client-wss-vkcloud.js
       └── ...
   ```

### Шаг 2: Проверьте доступность
После загрузки проверьте доступность файлов:
- `https://lizamsg.ru/simple-signal-test-websocket.html`
- `https://lizamsg.ru/webrtc-test-simple.html`
- `https://lizamsg.ru/contacts-app.html`

## 🧪 **Тестирование:**

### Основной тест WebSocket:
1. Откройте `https://lizamsg.ru/simple-signal-test-websocket.html` в двух вкладках
2. Запустите User 1 и User 2
3. Отправьте ping - должен работать мгновенно!
4. Создайте WebRTC соединение

### Полный тест WebRTC:
1. Откройте `https://lizamsg.ru/webrtc-test-simple.html` в двух вкладках
2. Протестируйте все стадии: авторизация → ping → offer → answer → разрыв

## ✅ **Ожидаемые результаты:**

- ✅ **Быстрый ping** - отправка за секунды, а не минуты
- ✅ **Автоматический pong** - ответ приходит сразу
- ✅ **Стабильное WebRTC** - соединение устанавливается быстро
- ✅ **Качественное видео/аудио** - передача без задержек

## 🔧 **Техническая информация:**

**Архитектура:**
- **Фронтенд:** `https://lizamsg.ru/` (HTML файлы)
- **Backend API:** `https://lizamsg.ru:3000/api/signaling` (HTTP API)
- **WebSocket:** `wss://lizamsg.ru:8080` (WSS сервер)
- **TURN сервер:** `62.84.126.200:3478/3479` (VK Cloud)

**База данных:** НЕ НУЖНА - используется WebSocket сервер в памяти

## 🎉 **Готово к тестированию!**

После загрузки все должно работать идеально - ping будет отправляться мгновенно, а WebRTC соединения устанавливаться быстро!
