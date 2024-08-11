<?php

$token = ""; // Токен бота
$setting_host_bd = ""; // Хост БД
$setting_user_bd = ""; // Пользователь БД
$setting_password_bd = ""; // Пароль БД
$setting_name_bd = ""; // Имя БД
$setting_short_encoding_bd = ""; // Кодировка
$setting_encoding_bd = ""; // Кодировка
$admin_id = ""; // TG_ID администратора

// Подключене к БД и установка кодировки
$mysqli = new mysqli($setting_host_bd, $setting_user_bd, $setting_password_bd, $setting_name_bd);
$mysqli->query("SET NAMES {$setting_short_encoding_bd}, collation_connection = '{$setting_encoding_bd}'");

// Функции БД
class Database {
    // Получение информации о пользователе
    public static function user($from_id) {
        global $mysqli;
        $res = $mysqli->query("SELECT * FROM `users` WHERE tg_id = {$from_id}");
        // | Обработка данных:
        $row = $res->fetch_assoc();
        return([$row['ban'], $row['vpn'], $row['mode'], $row['message']]);
    }
    // Изменение поля mode пользователя
    public static function change_mode($from_id, $mode) {
        global $mysqli;
        $mysqli->query("UPDATE `users` SET `mode` = '{$mode}' WHERE `users`.`tg_id` = {$from_id};");
    }
    // Изменение поля message пользователя
    public static function change_message($from_id, $message) {
        global $mysqli;
        $mysqli->query("UPDATE `users` SET `message` = '{$message}' WHERE `users`.`tg_id` = {$from_id};");
    }
    // Изменение поля vpn пользователя
    public static function change_vpn($from_id, $vpn) {
        global $mysqli;
        $mysqli->query("UPDATE `users` SET `vpn` = '{$vpn}' WHERE `users`.`tg_id` = {$from_id};");
    }
    // Проверка наличия свободных ключей
    public static function vpn_check() {
        global $mysqli;
        $res = $mysqli->query("SELECT * FROM `keys` WHERE `tg_id` = 0");
        // | Обработка данных:
        $row = $res->fetch_assoc();
        return($row["name"]);
    }
    // Получение нового ключа
    public static function generate_vpn($from_id) {
        global $mysqli;
        $res = $mysqli->query("SELECT * FROM `keys` WHERE `tg_id` = {$from_id}");
        $row = $res->fetch_assoc();
        if ($row["name"] != "") {
            $mysqli->query("UPDATE `keys` SET `tg_id` = '1' WHERE `keys`.`tg_id` = {$from_id}; ");
        }
        $res = $mysqli->query("SELECT * FROM `keys` WHERE `tg_id` = 0");
        // | Обработка данных:
        $row = $res->fetch_assoc();
        $mysqli->query("UPDATE `keys` SET `tg_id` = '{$from_id}' WHERE `keys`.`id` = {$row["id"]}; ");
        return([$row["name"], $row["outline"]]);
    }
    // Восстановление полученного ключа
    public static function restore_vpn($from_id) {
        global $mysqli;
        $res = $mysqli->query("SELECT * FROM `keys` WHERE `tg_id` = {$from_id}");
        // | Обработка данных:
        $row = $res->fetch_assoc();
        return([$row["name"], $row["outline"]]);
    }
    // Удаление пользователя
    public static function delete($from_id) {
        global $mysqli;
        $mysqli->query("DELETE FROM `users` WHERE `users`.`tg_id` = {$from_id}");
    }
}

// Функции Telegram API
class TGAPI {
    // Отправка POST
    public static function curl($ch, $arrayQuery) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayQuery);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        
	    return(json_decode($res));
    }
    // Отправка текстового сообщения
    public static function send_message($text, $from_id) {
        global $token;
        $arrayQuery = array(
            "chat_id" => $from_id,
            "text" => $text,
            "parse_mode" => "HTML"
        );		
        $ch = curl_init('https://api.telegram.org/bot'. $token .'/sendMessage');

	    return(TGAPI::curl($ch, $arrayQuery));
    }
    // Отправка текстового сообщения с inline
    public static function send_inline($text, $keyboard, $from_id) {
        global $token;
        $arrayQuery = array(
            "chat_id" => $from_id,
            "text" => $text,
            "parse_mode" => "HTML",
            "reply_markup" => $keyboard
        );		
        $ch = curl_init('https://api.telegram.org/bot'. $token .'/sendMessage');

        return(TGAPI::curl($ch, $arrayQuery));
    }
    // Изменение текстового сообщения
    public static function edit_message($text, $message_id, $from_id) {
        global $token;
        $arrayQuery = array(
            "chat_id" => $from_id,
            "text" => $text,
            "parse_mode" => "HTML",
            "message_id" => $message_id
        );		
        $ch = curl_init('https://api.telegram.org/bot'. $token .'/editMessageText');
        TGAPI::curl($ch, $arrayQuery);
    }
    // Изменение текстового сообщения с inline
    public static function edit_message_inline($text, $message_id, $from_id, $keyboard) {
        global $token;
        $arrayQuery = array(
            "chat_id" => $from_id,
            "text" => $text,
            "parse_mode" => "HTML",
            "message_id" => $message_id,
            "reply_markup" => $keyboard
        );		
        $ch = curl_init('https://api.telegram.org/bot'. $token .'/sendMessage');
        TGAPI::curl($ch, $arrayQuery);
    }
    // Отправка файла
    public static function send_document($name, $from_id, $caption) {
        global $token;
        $arrayQuery = array(
            "chat_id" => $from_id,
            "caption" => $caption,
            "document" => curl_file_create(__DIR__ . "/{$name}")
        );		
        $ch = curl_init('https://api.telegram.org/bot'. $token .'/sendDocument');
        TGAPI::curl($ch, $arrayQuery);
    }
}

// Функции для сообщений
class Message {
    // Генерация проверки
    public static function checking() {
        $array = ["🎲", "🍄", "🎉"];
        shuffle($array); // Перемешивание массива
        $rand = rand(0, 2); // Выбор правильного эмодзи
        // Шаблон клавиатуры
        $keyboard = ["inline_keyboard" => [[["text" => "", "callback_data" => "no"], ["text" => "", "callback_data" => "no"], ["text" => "", "callback_data" => "no"]]]];
        // Добавление эмодзи и правильного ответа
        $keyboard["inline_keyboard"][0][0]["text"] = $array[0];
        $keyboard["inline_keyboard"][0][1]["text"] = $array[1];
        $keyboard["inline_keyboard"][0][2]["text"] = $array[2];
        $keyboard["inline_keyboard"][0][$rand]["callback_data"] = "yes";

        return([$array[$rand], json_encode($keyboard)]);
    }
    // Генерация нового ключа
    public static function generate_new($from_id, $message_id) {
        $vpn = Database::generate_vpn($from_id);
        $text = "Для подключения к VPN необходимо скачать приложение <b>Outline</b> (зелёная и белая полусферы на аве). Это VPN-клиент, который доступен на всех платформах. При первом открытии приложение попросит ввести ключ, он указан ниже. Пожалуйста, <b>храни его в безопасности и НИКОМУ не передавай. А лучше — вообще его не храни!</b>\n\nТак же советую тебе скачать дополнительный VPN-клиент с улучшенным обходом блокировок — <b>Amnezia</b> (буква А на аве)! При первом открытии приложение попросит ввести ключ, <b> ключом является файл</b>, который прикреплён к этому сообщению. Правила обращения с ним те же, что с ключом Outline!\n\nНа этом всё, удачи тебе, <b>{$vpn[0]}</b>!\n\n<pre>{$vpn[1]}</pre>";
        TGAPI::edit_message($text, $message_id, $from_id);
        $name = md5($vpn[0]);
        TGAPI::send_document("vpn/{$name}.vpn", $from_id, "Ключ Amnezia");
    }
    // Восстановление ключа
    public static function restore($from_id, $message_id) {
        $vpn = Database::restore_vpn($from_id);
        $text = "Для подключения к VPN необходимо скачать приложение <b>Outline</b> (зелёная и белая полусферы на аве). Это VPN-клиент, который доступен на всех платформах. При первом открытии приложение попросит ввести ключ, он указан ниже. Пожалуйста, <b>храни его в безопасности и НИКОМУ не передавай. А лучше — вообще его не храни!</b>\n\nТак же советую тебе скачать дополнительный VPN-клиент с улучшенным обходом блокировок — <b>Amnezia</b> (буква А на аве)! При первом открытии приложение попросит ввести ключ, <b> ключом является файл</b>, который прикреплён к этому сообщению. Правила обращения с ним те же, что с ключом Outline!\n\nНа этом всё, удачи тебе, <b>{$vpn[0]}</b>!\n\n<pre>{$vpn[1]}</pre>";
        TGAPI::edit_message($text, $message_id, $from_id);
        $name = md5($vpn[0]);
        TGAPI::send_document("vpn/{$name}.vpn", $from_id, "Ключ Amnezia");
    }
    // Логгирование
    public static function delete_id($from_id, $message_id) {
        $text = file_get_contents("logs.txt");
        $text .= "{$from_id} - {$message_id}";
        file_put_contents("logs.txt", $text);
    }
}