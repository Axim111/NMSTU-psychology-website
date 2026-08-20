<?php

namespace App\Core\Notifications;

/**
 * Один канал доставки уведомления. Сейчас есть два стаба:
 * EmailNotifier (почта портала) и PortalNotifier (колокольчик на
 * портале — как на скриншоте "Уведомления" в личном кабинете).
 *
 * Когда портал даст доступ к своему почтовому шлюзу и к API
 * уведомлений — здесь появятся EmailNotifier и PortalNotifier с
 * реальной отправкой вместо записи в notification_log. Остальной
 * код (NotificationDispatcher, вызовы из контроллеров) трогать не
 * придётся — тот же принцип, что и с AuthProviderInterface.
 */
interface NotifierInterface
{
    /**
     * @param array<string,mixed> $recipient Строка users: id, email, last_name, first_name...
     */
    public function send(array $recipient, string $subject, string $message): void;
}
