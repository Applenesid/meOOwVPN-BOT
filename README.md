# meOOwVPN-BOT

A bot that allows you to send vpngen keys to users\n
Бот, позволяющий отправлять пользователям ключи vpngen\n\n

**main.php** — основной файл бота\n
**setting.php** — дополнительный файл с настройками\n\n

СТРУКТУРА БД:\n\n

**users**:\n
| id            | tg_id           | vpn       | ban       | mode      | message   |
|---------------|-----------------|-----------|-----------|-----------|-----------|
| int, PK-NN-AI | varchar(15), NN | int, NN:0 | int, NN:0 | int, NN:0 | int, NN:0 |
| 1             | 123456789       | 0         | 0         | 0         | 0         |
\n\n
**keys**:\n
| id            | name               | outline      | tg_id             |
|---------------|--------------------|--------------|-------------------|
| int, PK-NN-AI | text, NN           | longtext, NN | varchar(15), NN:0 |
| 1             | 000 Лимонадный Джо | ss://key     | 123456789         |
