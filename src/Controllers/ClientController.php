<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifications\NotificationDispatcher;

class ClientController
{
    /**
     * GET /cabinet
     * Список своих записей (будущих и прошедших) + отмена.
     */
    public function cabinet(): void
    {
        Auth::requireRole('client');

        $stmt = Database::connection()->prepare(
            "SELECT a.id, a.status, a.request_comment,
                    s.slot_date, s.start_time,
                    u.last_name, u.first_name, u.patronymic
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN psychologist_profiles p ON p.id = s.psychologist_id
             JOIN users u ON u.id = p.user_id
             WHERE a.client_id = ?
             ORDER BY s.slot_date DESC, s.start_time DESC"
        );
        $stmt->execute([Auth::id()]);
        $appointments = $stmt->fetchAll();

        require __DIR__ . '/../../templates/dashboard/client_cabinet.php';
    }

    /**
     * POST /cabinet/cancel
     * Отмена своей записи. По ТЗ — не позже чем за 24 часа.
     */
    public function cancel(): void
    {
        Auth::requireRole('client');

        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT a.id, a.slot_id, a.status, s.slot_date, s.start_time,
                    u.last_name AS psych_last_name, u.first_name AS psych_first_name
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN psychologist_profiles p ON p.id = s.psychologist_id
             JOIN users u ON u.id = p.user_id
             WHERE a.id = ? AND a.client_id = ?"
        );
        $stmt->execute([$appointmentId, Auth::id()]);
        $appointment = $stmt->fetch();

        if (!$appointment || $appointment['status'] !== 'active') {
            header('Location: /cabinet');
            return;
        }

        $slotStartsAt = strtotime($appointment['slot_date'] . ' ' . $appointment['start_time']);
        if ($slotStartsAt - time() < 24 * 3600) {
            $_SESSION['cabinet_error'] = 'Отменить можно не позже чем за 24 часа до приёма.';
            header('Location: /cabinet');
            return;
        }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE appointments SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?")
                ->execute([$appointment['id']]);
            $db->prepare("UPDATE schedule_slots SET status = 'free' WHERE id = ?")
                ->execute([$appointment['slot_id']]);
            $db->commit();

            // Оповещение об отмене (заглушка — см. src/Core/Notifications/).
            $clientRow = $db->prepare('SELECT id, email, last_name, first_name FROM users WHERE id = ?');
            $clientRow->execute([Auth::id()]);
            $client = $clientRow->fetch();
            if ($client) {
                NotificationDispatcher::default()->notify(
                    $client,
                    'Запись отменена',
                    sprintf(
                        'Ваша запись к психологу %s %s на %s в %s отменена.',
                        $appointment['psych_last_name'],
                        $appointment['psych_first_name'],
                        date('d.m.Y', strtotime($appointment['slot_date'])),
                        substr($appointment['start_time'], 0, 5)
                    )
                );
            }
        } catch (\Throwable $e) {
            $db->rollBack();
        }

        header('Location: /cabinet');
    }
}
