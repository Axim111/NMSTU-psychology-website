<?php

namespace App\Core\Notifications;

use App\Core\Database;

/**
 * Отправка через Telegram-бота (прототип канала "мессенджер MAX" —
 * пока у нас нет доступа к Bot API самого MAX, канал делается на
 * Telegram; когда доступ появится, у MAX тоже есть свой Bot API
 * (dev.max.ru), нужно будет просто завести MaxNotifier с той же
 * сигнатурой и переключить NotificationDispatcher::default() на
 * него вместо TelegramNotifier).
 *
 * Требует, чтобы у получателя было заполнено users.telegram_chat_id —
 * его проставляет сам бот (см. bin/telegram-bot.php) при команде
 * /start с привязкой по email. Если получатель ещё не привязан —
 * молча пишет заглушку в notification_log со статусом stub, ничего
 * не отправляет.
 */
class TelegramNotifier implements NotifierInterface
{
    public function send(array $recipient, string $subject, string $message): void
    {
        try {
            $chatId = $this->chatIdFor((int) $recipient['id']);
        } catch (\Throwable $e) {
            // Отсутствует столбец telegram_chat_id — миграция не применена.
            // Тихо пропускаем, чтобы не ронять запись/отмену приёма.
            return;
        }

        $token = getenv('TELEGRAM_BOT_TOKEN') ?: null;

        if (!$chatId || !$token) {
            try {
                $this->log($recipient['id'], $subject, $message, 'stub');
            } catch (\Throwable $e) {
                // Отсутствует канал 'telegram' в ENUM notification_log —
                // миграция не применена. Тихо пропускаем.
            }
            return;
        }

        $text = $subject . "\n\n" . $message;
        $ok = $this->callTelegramApi($token, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        try {
            $this->log($recipient['id'], $subject, $message, $ok ? 'sent' : 'failed');
        } catch (\Throwable $e) {
            // То же — миграция на notification_log ещё не применена.
        }
    }

    private function chatIdFor(int $userId): ?string
    {
        $stmt = Database::connection()->prepare('SELECT telegram_chat_id FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: null;
    }

    private function callTelegramApi(string $token, string $method, array $params): bool
    {
        $url = "https://api.telegram.org/bot{$token}/{$method}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);

        if ($error || $response === false) {
            return false;
        }

        $decoded = json_decode($response, true);
        return !empty($decoded['ok']);
    }

    private function log(int $userId, string $subject, string $message, string $status): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO notification_log (user_id, channel, subject, message, status)
             VALUES (?, 'telegram', ?, ?, ?)"
        );
        $stmt->execute([$userId, $subject, $message, $status]);
    }
}
