<?php

/**
 * Telegram-бот — привязка аккаунта студента/психолога/админа к чату для
 * получения уведомлений. Это прототип канала MAX (см. комментарий в
 * TelegramNotifier.php — на MAX своя Bot API, dev.max.ru).
 *
 * Что умеет:
 *   /start — приветствие с инструкцией
 *   любое сообщение с email — если email найден в users, привязывает
 *   telegram_chat_id этого чата к пользователю
 *
 * Запуск — отдельный процесс, слушает Telegram через long polling
 * (getUpdates), а не через веб-запросы. В Docker — отдельный сервис
 * telegram-bot в docker-compose.yml.
 *
 * Без TELEGRAM_BOT_TOKEN в переменных окружения бот не подключается
 * к Telegram вообще и просто ждёт (лог "не настроен") — это и есть
 * заглушка на случай, если токена ещё нет, но контейнер уже должен
 * подниматься вместе с остальными.
 */

require __DIR__ . '/../src/Core/Database.php';

use App\Core\Database;

$token = getenv('TELEGRAM_BOT_TOKEN') ?: null;

if (!$token) {
    fwrite(STDOUT, "[telegram-bot] TELEGRAM_BOT_TOKEN не задан — бот не настроен, простаиваю.\n");
    while (true) {
        sleep(60);
    }
}

fwrite(STDOUT, "[telegram-bot] Запущен, слушаю обновления...\n");

$offset = 0;

while (true) {
    try {
        $updates = telegramApiCall($token, 'getUpdates', [
            'offset' => $offset,
            'timeout' => 30,
        ]);
    } catch (\Throwable $e) {
        fwrite(STDERR, "[telegram-bot] Ошибка запроса getUpdates: {$e->getMessage()}\n");
        sleep(10);
        continue;
    }

    if ($updates === null) {
        sleep(5);
        continue;
    }

    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        try {
            handleUpdate($token, $update);
        } catch (\Throwable $e) {
            fwrite(STDERR, "[telegram-bot] Ошибка обработки update " . $update['update_id'] . ": {$e->getMessage()}\n");
        }
    }
}

function handleUpdate(string $token, array $update): void
{
    $message = $update['message'] ?? null;
    if (!$message || !isset($message['text'], $message['chat']['id'])) {
        return;
    }

    $chatId = (string) $message['chat']['id'];
    $text = trim($message['text']);

    if (str_starts_with($text, '/start')) {
        telegramApiCall($token, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Здравствуйте! Чтобы получать уведомления о записях, отправьте сюда свою почту, указанную на сайте.',
        ]);
        return;
    }

    if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
        linkChatToUser($token, $chatId, $text);
        return;
    }

    telegramApiCall($token, 'sendMessage', [
        'chat_id' => $chatId,
        'text' => 'Не похоже на email. Отправьте почту, указанную на сайте, чтобы привязать уведомления.',
    ]);
}

function linkChatToUser(string $token, string $chatId, string $email): void
{
    $db = Database::connection();

    try {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
    } catch (\Throwable $e) {
        fwrite(STDERR, "[telegram-bot] Ошибка поиска пользователя по email: {$e->getMessage()}\n");
        telegramApiCall($token, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Ошибка на сервере. Попробуйте позже.',
        ]);
        return;
    }

    if (!$userId) {
        telegramApiCall($token, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Не нашёл такой email на сайте. Проверьте и попробуйте ещё раз.',
        ]);
        return;
    }

    try {
        $stmt = $db->prepare('UPDATE users SET telegram_chat_id = ? WHERE id = ?');
        $stmt->execute([$chatId, $userId]);
    } catch (\Throwable $e) {
        fwrite(STDERR, "[telegram-bot] Ошибка обновления telegram_chat_id (столбец отсутствует? примените миграцию database/migrations/2026_09_06_telegram_reschedule.sql): {$e->getMessage()}\n");
        telegramApiCall($token, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Технические работы: привязка временно недоступна, попробуйте позже.',
        ]);
        return;
    }

    telegramApiCall($token, 'sendMessage', [
        'chat_id' => $chatId,
        'text' => 'Готово, аккаунт привязан. Теперь уведомления о записях будут приходить сюда.',
    ]);
}

/**
 * @return array|null Массив result у getUpdates, true/false/null у остальных методов
 */
function telegramApiCall(string $token, string $method, array $params)
{
    $url = "https://api.telegram.org/bot{$token}/{$method}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $params['timeout'] ?? 10 + 5,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }

    $decoded = json_decode($response, true);
    if (empty($decoded['ok'])) {
        return null;
    }

    return $method === 'getUpdates' ? $decoded['result'] : true;
}
