<?php
echo "PHP работает!<br>";
echo "Время: " . date('Y-m-d H:i:s') . "<br>";
echo "Версия PHP: " . phpversion() . "<br>";

session_start();
echo "Сессия: " . (isset($_SESSION['user_id']) ? "ID = " . $_SESSION['user_id'] : "Не авторизован") . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "✅ Пользователь авторизован<br>";
} else {
    echo "❌ Пользователь НЕ авторизован<br>";
}
?>
