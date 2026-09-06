<?php

namespace App\Core\Notifications;

/**
 * Рассылает одно уведомление сразу по всем подключённым каналам
 * (сейчас — email + портал). Ошибка в одном канале не должна ронять
 * основной сценарий (запись на приём всё равно должна создаться,
 * даже если "отправка" уведомления почему-то упала) — поэтому каждый
 * канал обёрнут в try/catch.
 *
 * Использование из контроллера:
 *   NotificationDispatcher::default()->notify(
 *       $clientRow, 'Запись подтверждена', 'Вы записаны на ...'
 *   );
 */
class NotificationDispatcher
{
    /** @var NotifierInterface[] */
    private array $channels;

    public function __construct(array $channels)
    {
        $this->channels = $channels;
    }

    public static function default(): self
    {
        return new self([
            new EmailNotifier(),
            new PortalNotifier(),
            new TelegramNotifier(),
        ]);
    }

    public function notify(array $recipient, string $subject, string $message): void
    {
        foreach ($this->channels as $channel) {
            try {
                $channel->send($recipient, $subject, $message);
            } catch (\Throwable $e) {
                // Заглушка не должна ронять запись на приём — молча пропускаем.
                // Когда каналы станут настоящими, здесь стоит добавить логирование ошибки.
            }
        }
    }
}
