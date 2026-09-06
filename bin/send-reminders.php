<?php

/**
 * Напоминания о записях на завтра.
 *
 * ЗАГЛУШКА-КАРКАС: сама рассылка (см. src/Core/Notifications/) пока
 * ничего реально не отправляет, только пишет в notification_log.
 * Но сам механизм "раз в день пройтись по завтрашним записям и
 * напомнить" — уже рабочий, его не переделывать, когда подключат
 * реальные каналы.
 *
 * Не вызывается из веб-запросов. Предполагается запуск по cron,
 * например раз в сутки:
 *   0 9 * * * docker compose exec -T php php bin/send-reminders.php
 *
 * (Точный способ запуска внутри Docker — на усмотрение того, кто
 * будет разворачивать прод: cron на хосте с docker compose exec,
 * либо отдельный контейнер с cron внутри. Тут не решаем.)
 */

require __DIR__ . '/../src/Core/Database.php';
require __DIR__ . '/../src/Core/Notifications/NotifierInterface.php';
require __DIR__ . '/../src/Core/Notifications/EmailNotifier.php';
require __DIR__ . '/../src/Core/Notifications/PortalNotifier.php';
require __DIR__ . '/../src/Core/Notifications/TelegramNotifier.php';
require __DIR__ . '/../src/Core/Notifications/NotificationDispatcher.php';

use App\Core\Database;
use App\Core\Notifications\NotificationDispatcher;

$db = Database::connection();

$stmt = $db->query(
    "SELECT a.id, u.id AS client_id, u.email, u.last_name, u.first_name,
            s.start_time, pu.last_name AS psych_last_name, pu.first_name AS psych_first_name
     FROM appointments a
     JOIN schedule_slots s ON s.id = a.slot_id
     JOIN psychologist_profiles p ON p.id = s.psychologist_id
     JOIN users pu ON pu.id = p.user_id
     JOIN users u ON u.id = a.client_id
     WHERE a.status = 'active' AND s.slot_date = CURDATE() + INTERVAL 1 DAY"
);
$appointments = $stmt->fetchAll();

$dispatcher = NotificationDispatcher::default();
$count = 0;

foreach ($appointments as $a) {
    $dispatcher->notify(
        ['id' => $a['client_id'], 'email' => $a['email']],
        'Напоминание о завтрашней записи',
        sprintf(
            'Завтра в %s у вас приём у психолога %s %s.',
            substr($a['start_time'], 0, 5),
            $a['psych_last_name'],
            $a['psych_first_name']
        )
    );
    $count++;
}

echo "Отправлено напоминаний (заглушка): {$count}\n";
