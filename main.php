<?php

require "setting.php"; // Подключение настроек

$json_data = json_decode(file_get_contents('php://input')); // Получение запроса

if (($json_data -> message -> from -> id) != "") {
    $from_id = $json_data -> message -> from -> id;
    $text = $json_data -> message -> text;
    $message_id = $json_data -> message -> message_id;
} else {
    $callback = $json_data -> callback_query -> data;
    $explode = explode("-", $callback);
    $from_id = $json_data -> callback_query -> from -> id;
    $message_id = $json_data -> callback_query -> message -> message_id;
}

$user_bd = Database::user($from_id); // Информация о пользователе из БД

if ($user_bd[0] == "") {
    // Пользователь отсутствует в БД
    TGAPI::send_message("Извини, тебя ещё не добавили в бота...", $from_id);
    exit();
} elseif ($user_bd[0] == 2) {
    // Пользователь заблокирован навсегда
    TGAPI::send_message("<b>Твой аккаунт заблокирован навсегда!</b> К сожалению, ты больше не сможешь воспользоваться ботом...", $from_id);
} elseif ($text == "/support") {
    // Обращение в службу поддержки
    $res = TGAPI::send_inline("Напиши своё сообщение в <b>службу поддержки</b>, мы постараемся ответить как можно скорее!\n\nНо, пожалуйста, не засоряй этот канал связи, иначе получишь бан...", json_encode(["inline_keyboard" => [[["text" => "❌ Отмена", "callback_data" => "cancel"]]]]), $from_id);
    Database::change_mode($from_id, 1);
    Database::change_message($from_id, $res->result->message_id);
} elseif ($user_bd[0] == 1) {
    // Пользователь заблокирован
    TGAPI::send_message("<b>Твой аккаунт заблокирован!</b> Видимо кто-то нарушил правила...\n\nНо если это не ты, то напиши /support, мы разберёмся в ситуации!", $from_id);
} elseif ($text == "Выход" || $text == "/exit" || $text == "Отмена") {
    // Выйти из раздела
    TGAPI::send_message("Возвращаем назад...", $from_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
} elseif ($text == "/start") {
    // Приветствие
    TGAPI::send_message("Привет! Я <b>котик-VPNотик</b>, твой личный помощник в вопросах VPN!\n\nДля подключения к VPN напиши /new\nДля восстановления VPN напиши /restore\n\nНастоятельно рекомендую установить <b>автоудаление сообщений</b>! Для этого зайди в профиль бота, нажми на три точки сверху, выбери пункт \"Автоудаление\" и активируй автоудаление на <b>1 день</b>", $from_id);
} elseif ($text == "/new" && $user_bd[1] <= 3) {
    // Выдача нового ключа: правила
    $res = TGAPI::send_inline("Для начала <b>ознакомься с правилами</b>! Их нужно соблюдать, иначе получишь бан...\n\n⛔️ <b>Запрещается</b> использовать Торрент\n⛔️ <b>Запрещается</b> использовать \"хакерские\" инструменты\n⛔️ <b>Запрещается</b> рассылать спам\n⛔️ В месяц установлен лимит в <b>100 Гб</b>\n⛔️ Скорость ограничена до <b>10 Мбит/с</b>\n⛔️ Каждый месяц можно генерировать не более <b>3-х</b> ключей\n⛔️ При генерации нового ключа <b>старый деактивируется</b>\n\nДля продолжения нажми на кнопку ниже...", json_encode(["inline_keyboard" => [[["text" => "✅ Ознакомился", "callback_data" => "rule-yes"], ["text" => "❌ Отмена", "callback_data" => "rule-no"]]]]), $from_id);
    Database::change_mode($from_id, 2);
    Database::change_message($from_id, $res->result->message_id);
} elseif ($text == "/restore" && $user_bd[1] > 0) {
    // Восстановление ключа: проверка
    $checking = Message::checking();
    $res = TGAPI::send_inline("Пожалуйста, <b>подтверди действие</b>!\n\nВыбери смайлик {$checking[0]} из предложенных", $checking[1], $from_id);
    Database::change_mode($from_id, 3);
    Database::change_message($from_id, $res->result->message_id);
} elseif ($text == "/restore" && $user_bd[1] == 0) {
    // Восстановление ключа: ключ отсутствует
    TGAPI::send_message("У тебя ещё нет VPN! Создай <b>новый ключ</b> командой /new", $from_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
} elseif ($text == "/invite") {
    // Пригласить другого пользователя
    $res = TGAPI::send_inline("Ты можешь пригласить друга в бота! Для этого отправь <b>ссылку на его профиль</b>\n\nУчти, что проверка пользователя происходит в ручную, а это значит, что он может быть добавлен с задержкой или не добавлен вовсе", json_encode(["inline_keyboard" => [[["text" => "❌ Отмена", "callback_data" => "cancel"]]]]), $from_id);
    Database::change_mode($from_id, 4);
    Database::change_message($from_id, $res->result->message_id);
} elseif ($text == "/help") {
    // Информация о боте
    TGAPI::send_message("Ключи VPN любезно предоставлены <b>проектом</b> @vpngen\n\nИнструкции по <b>Outline</b> и <b>Amnezia</b> прикреплены к сообщению\n\n<b>Разработчик</b> бота: @applenesid", $from_id);
    TGAPI::send_document("instructions/outline.pdf", $from_id, "Инструкция Outline");
    TGAPI::send_document("instructions/amnezia.pdf", $from_id, "Инструкция Amnezia");
} elseif ($text == "/delete") {
    // Удаление пользователя: проверка
    $checking = Message::checking();
    $res = TGAPI::send_inline("Пожалуйста, <b>подтверди действие</b>!\n\nВыбери смайлик {$checking[0]} из предложенных", $checking[1], $from_id);
    Database::change_mode($from_id, 5);
    Database::change_message($from_id, $res->result->message_id);
} elseif ($callback == "rule-no" || $callback == "cancel") {
    // Отмена действия
    TGAPI::edit_message("Действие отменено!", $user_bd[3], $from_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
} elseif ($user_bd[2] == 1) {
    // Обращение в службу поддержки: отправлено
    TGAPI::send_message("Твоё обращение принято и доставлено администратору!", $from_id);
    TGAPI::send_inline("<b>НОВОЕ ОБРАЩЕНИЕ!</b>\n\n<b>ID:</b> {$from_id}\n\n<b>ТЕКСТ: </b>{$text}", json_encode(["inline_keyboard" => [[["text" => "✏️ Ответить", "callback_data" => "answer-{$from_id}"]]]]), $admin_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
} elseif ($user_bd[2] == 2 && ($callback == "rule-yes" || $text == "Ознакомлен") && Database::vpn_check() != "" && $user_bd[1] <= 2) {
    // Выдача нового ключа: успешно
    Message::generate_new($from_id, $message_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
    Database::change_vpn($from_id, $user_bd[1]+1);
} elseif ($user_bd[2] == 2 && ($callback == "rule-yes" || $text == "Ознакомлен") && Database::vpn_check() == "") {
    // Выдача нового ключа: нет ключей
    TGAPI::edit_message("Извини, в данный момент нет свободных ключей... Попробуй попозже, я уже сообщил администратору, что ключи закончились!", $user_bd[3], $from_id);
    TGAPI::send_inline("<b>НЕТ КЛЮЧЕЙ!</b>\n\n<b>ID:</b> {$from_id}", json_encode(["inline_keyboard" => [[["text" => "✅ Ответить", "callback_data" => "key-{$from_id}"]]]]), $admin_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
} elseif ($user_bd[2] == 2 && $user_bd[1] >= 3) {
    // Выдача нового ключа: превышен лимит
    TGAPI::edit_message("Извини, ты исчерпал свой запас ключей... Через месяц счётчик обновится и ты сможешь сгенерировать ключ!", $user_bd[3], $from_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
} elseif ($user_bd[2] == 3 && $callback == "yes") {
    // Восстановление ключа: успешно
    Message::restore($from_id, $message_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
} elseif (($user_bd[2] == 3 && $callback == "no") || ($user_bd[2] == 5 && $callback == "no") || ($user_bd[2] == 6 && $callback == "no")) {
    // Ошибка в подтверждении действия
    $checking = Message::checking();
    $res = TGAPI::edit_message_inline("Ты ошибся, пожалуйста, <b>попробуй ещё</b>!\n\nВыбери смайлик {$checking[0]} из предложенных\n\nДля выхода напиши /exit", $user_bd[3], $from_id, $checking[1]);
} elseif ($user_bd[2] == 4) {
    // Пригласить другого пользователя: успешно
    TGAPI::send_message("Твоё обращение принято и доставлено администратору!", $from_id);
    TGAPI::send_inline("<b>НОВЫЙ ПОЛЬЗОВАТЕЛЬ!</b>\n\n<b>ID:</b> {$from_id}\n\n<b>ТЕКСТ: </b>{$text}", json_encode(["inline_keyboard" => [[["text" => "✅ Ответить", "callback_data" => "user-{$from_id}"], ["text" => "✏️ Ответить", "callback_data" => "userans-{$from_id}"]]]]), $admin_id);
    Database::change_mode($from_id, 0);
    Database::change_message($from_id, 0);
} elseif ($user_bd[2] == 5 && $callback == "yes") {
    // Удаление пользователя: финальная проверка
    $checking = Message::checking();
    $res = TGAPI::edit_message_inline("<b>ВНИМАНИЕ!</b>\n\nДанное действие <b>ПОЛНОСТЬЮ</b> удалит твой профиль! Ты больше не сможешь пользоваться ботом!\n\nПодумай ещё раз и выбери смайлик {$checking[0]} для подтверждения действия\n\nДля выхода напиши /exit", $user_bd[3], $from_id, $checking[1]);
    Database::change_mode($from_id, 6);
} elseif ($user_bd[2] == 6 && $callback == "yes") {
    // Удаление пользователя: успешно
    $checking = Message::checking();
    $res = TGAPI::edit_message("Поздравляю! Твой профиль удалён! Для восстановления напиши администратору", $user_bd[3], $from_id);
    Database::delete($from_id);
} elseif ($explode[0] == "answer") {
    // Обращение в службу поддержки: ответ
    TGAPI::send_message("Напишите ответ пользователю!", $admin_id);
    Database::change_mode($from_id, 7);
    Database::change_message($from_id, $explode[1]);
} elseif ($explode[0] == "key") {
    // Сообщение о появлении новых ключей
    TGAPI::send_message("✅ Ключи добавлены!", $explode[1]);
    TGAPI::send_message("Ответ отправлен!", $admin_id);
} elseif ($explode[0] == "user") {
    // Пригласить другого пользователя: ответ об успехе
    TGAPI::send_message("✅ Пользователь по твоему обращению добавлен!", $explode[1]);
    TGAPI::send_message("Ответ отправлен!", $admin_id);
} elseif ($explode[0] == "userans") {
    // Пригласить другого пользователя: ответ
    TGAPI::send_message("Напишите сообщение пользователю!", $admin_id);
    Database::change_mode($from_id, 8);
    Database::change_message($from_id, $explode[1]);
} elseif ($user_bd[2] == 7) {
    // Обращение в службу поддержки: ответ доставлен
    TGAPI::send_message("Получен ответ по твоему обращению!\n\n<b>Ответ:</b> {$text}", $user_bd[3]);
    TGAPI::send_message("Ответ отправлен!", $admin_id);
    Database::change_mode($from_id, 0);
} elseif ($user_bd[2] == 8) {
    // Пригласить другого пользователя: ответ доставлен
    TGAPI::send_message("Получен ответ по твоему обращению (пользователь)!\n\n<b>Ответ:</b> {$text}", $user_bd[3]);
    TGAPI::send_message("Ответ отправлен!", $admin_id);
    Database::change_mode($from_id, 0);
} else {
    // Другое сообщение
    TGAPI::send_message("Я тебя не понял... Используй меню команд, чтобы мы поняли друг друга!", $from_id);
} 