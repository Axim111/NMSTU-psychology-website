<?php

namespace App\Core\Notifications;

use App\Core\Database;

/**
 * ЗАГЛУШКА. Реальная отправка почты не подключена — портал отправляет
 * уведомления сам (видно на скриншоте личного кабинета), у нас пока
 * нет доступа ни к его почтовому шлюзу, ни к SMTP.
 *
 * Когда доступ появится, здесь вместо записи в notification_log
 * появится реальная отправка — например через PHPMailer/Symfony
 * Mailer, если это будет обычный SMTP, или через HTTP-запрос к API
 * портала, если рассылкой писем управляет он сам. Сигнатура метода
 * send() не изменится, так что вызывающий код (NotificationDispatcher)
 * трогать не придётся.
 */
class EmailNotifier implements NotifierInterface
{
    public function send(array $recipient, string $subject, string $message): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO notification_log (user_id, channel, subject, message, status)
             VALUES (?, 'email', ?, ?, 'stub')"
        );
        $stmt->execute([$recipient['id'], $subject, $message]);
    }
}
