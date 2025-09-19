-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Сен 08 2025 г., 15:24
-- Версия сервера: 10.5.26-MariaDB
-- Версия PHP: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `lizaapp_bd`
--

-- --------------------------------------------------------

--
-- Структура таблицы `calls`
--

CREATE TABLE IF NOT EXISTS `calls` (
  `id` int(11) NOT NULL,
  `caller_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `status` enum('ringing','answered','ended','missed') DEFAULT 'ringing',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `contacts`
--

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `contacts`
--

INSERT INTO `contacts` (`id`, `user_id`, `contact_id`, `status`, `created_at`) VALUES
(8, 12, 1, 'accepted', '2025-09-05 05:14:37'),
(9, 1, 12, 'accepted', '2025-09-05 05:15:15'),
(10, 3, 12, 'accepted', '2025-09-05 14:35:18'),
(11, 12, 3, 'accepted', '2025-09-05 14:35:22'),
(12, 1, 3, 'accepted', '2025-09-05 14:38:03'),
(13, 3, 1, 'accepted', '2025-09-05 14:38:18');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `phone`, `password_hash`, `created_at`) VALUES
(1, '+79182725362', '$2y$10$xIQur7epTxdgjSWz7vM..OBdADan.MhRDe68pjP5/jcWfyIwngZSS', '2025-09-02 08:52:31'),
(2, '+79298290753', '$2y$10$nbuCEcwdZE1HZEd9TKLRI.fRufsXCoLEjAt0v264jLqo/AFwPcqd.', '2025-09-02 08:55:03'),
(3, '+79180744427', '$2y$10$tEOqCLj77iUWPEpIZLmRZO1HCuxJs50lTXFQl4GEAp5tpSgeM3aCy', '2025-09-02 08:57:38'),
(4, '+79181111111', '$2y$10$CpbhIeQsMUfGct3YYHLnh.SPanxE.uHN0Fwk5mVKlUSSpO2cRYHkC', '2025-09-04 13:16:13'),
(7, '+79991234567', '$2y$10$qBAkUz8E3HqlX48bqAeOauKQx1P63oXUDlHyU9vAGZ26WBfbGaZxi', '2025-09-04 13:28:40'),
(8, '+79181234567', '$2y$10$7W1SSybhNsqSHRU6kEFwcehf1WlZH1GZilkJN/k4AOgtK.hjJesHy', '2025-09-04 13:29:04'),
(9, '+79189876543', '$2y$10$.swVlsD9QE/BCHbFcMP2l..J.6zhM./iVxK2nZp6UVC7Urkhr2u5y', '2025-09-04 13:35:30'),
(11, '+79998888888', '$2y$10$ofUvVZoqf2JZtcCh8SM.OOEQPJo7hSnv.v0zezmwwY2k2O4aLvrS.', '2025-09-04 14:04:04'),
(12, '+79182725363', '$2y$10$lDMdveMEzn3aryi1ejGXGuh9BrKui2aZktRQ2e2RLRBNBBfMIAvHq', '2025-09-04 15:15:42');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `calls`
--
ALTER TABLE `calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `caller_id` (`caller_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Индексы таблицы `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contact` (`user_id`,`contact_id`),
  ADD KEY `contact_id` (`contact_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `calls`
--
ALTER TABLE `calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `calls`
--
ALTER TABLE `calls`
  ADD CONSTRAINT `calls_ibfk_1` FOREIGN KEY (`caller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `calls_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `contacts_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `users` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
