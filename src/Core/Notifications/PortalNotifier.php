<?php

namespace App\Core\Notifications;

use App\Core\Database;

/**
 * ЗАГЛУШКА. Портальные уведомления (колокольчик в шапке личного
 * кабинета, см. скриншот) генерирует сам портал — у нас пока нет
 * доступа к его API уведомлений.
 *
 * Когда доступ появится (скорее всего — вызов веб-сервиса портала,
 * который создаёт запись в его таблице уведомлений для конкретного
 * пользователя), здесь вместо записи в notification_log появится
 * HTTP-запрос к этому веб-сервису. Сигнатура send() не изменится.
 */
class PortalNotifier implements NotifierInterface
{
    public function send(array $recipient, string $subject, string $message): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO notification_log (user_id, channel, subject, message, status)
             VALUES (?, 'portal', ?, ?, 'stub')"
        );
        $stmt->execute([$recipient['id'], $subject, $message]);
    }
}
