<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifications\NotificationDispatcher;
use PDO;

class AdminController
{
    /**
     * GET /admin
     * Список психологов + сводка по записям.
     */
    public function index(): void
    {
        Auth::requireRole('admin');

        $stmt = Database::connection()->query(
            "SELECT p.id, p.user_id, p.photo_path,
                    u.last_name, u.first_name, u.patronymic, u.email,
                    (SELECT COUNT(*) FROM schedule_slots s WHERE s.psychologist_id = p.id AND s.status = 'booked') AS booked_count
             FROM psychologist_profiles p
             JOIN users u ON u.id = p.user_id
             ORDER BY u.last_name"
        );
        $psychologists = $stmt->fetchAll();

        require __DIR__ . '/../../templates/admin/index.php';
    }

    /**
     * GET /admin/psychologists/new
     */
    public function newPsychologistForm(): void
    {
        Auth::requireRole('admin');
        $error = null;
        $directions = [];
        try {
            $directions = Database::connection()->query("SELECT id, name FROM directions ORDER BY id")->fetchAll();
        } catch (\Throwable $e) {
        }
        require __DIR__ . '/../../templates/admin/new_psychologist.php';
    }

    /**
     * POST /admin/psychologists
     * Заводит нового психолога — учётку в users + профиль.
     */
    public function createPsychologist(): void
    {
        Auth::requireRole('admin');

        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $patronymic = trim($_POST['patronymic'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $bio = trim($_POST['bio'] ?? '');
        $photoPath = trim($_POST['photo_path'] ?? '');
        $selectedDirections = $_POST['directions'] ?? [];

        if ($lastName === '' || $firstName === '' || $email === '' || $password === '') {
            $error = 'Заполните фамилию, имя, email и пароль.';
            $directions = [];
            try {
                $directions = Database::connection()->query("SELECT id, name FROM directions ORDER BY id")->fetchAll();
            } catch (\Throwable $e) {
            }
            require __DIR__ . '/../../templates/admin/new_psychologist.php';
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO users (role, last_name, first_name, patronymic, email, password_hash)
                 VALUES ('admin', ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $lastName, $firstName, $patronymic ?: null, $email,
                password_hash($password, PASSWORD_BCRYPT),
            ]);
            $userId = (int)$db->lastInsertId();

            $stmt = $db->prepare(
                "INSERT INTO psychologist_profiles (user_id, photo_path, bio) VALUES (?, ?, ?)"
            );
            $stmt->execute([$userId, $photoPath ?: null, $bio ?: null]);
            $profileId = (int)$db->lastInsertId();

            // направления (если выбраны)
            if (is_array($selectedDirections) && !empty($selectedDirections)) {
                $ins = $db->prepare("INSERT INTO psychologist_directions (psychologist_id, direction_id) VALUES (?, ?)");
                foreach ($selectedDirections as $dirId) {
                    $dirId = (int)$dirId;
                    if ($dirId > 0) {
                        $ins->execute([$profileId, $dirId]);
                    }
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $error = 'Не получилось создать психолога — возможно, такой email уже есть.';
            $directions = [];
            try {
                $directions = Database::connection()->query("SELECT id, name FROM directions ORDER BY id")->fetchAll();
            } catch (\Throwable $e2) {
            }
            require __DIR__ . '/../../templates/admin/new_psychologist.php';
            return;
        }

        header('Location: /admin');
    }

    /**
     * POST /admin/psychologists/delete
     * Удаление психолога (users + профиль каскадом).
     */
    public function deletePsychologist(): void
    {
        Auth::requireRole('admin');

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            header('Location: /admin');
            return;
        }

        $stmt = Database::connection()->prepare(
            "DELETE FROM users WHERE id = ? AND role = 'admin'"
        );
        $stmt->execute([$userId]);

        header('Location: /admin');
    }

    /**
     * GET /admin/schedule?psychologist_id=...
     * Управление расписанием любого психолога (как у него в кабинете).
     */
    public function schedule(): void
    {
        Auth::requireRole('admin');

        $psychologistId = (int)($_GET['psychologist_id'] ?? 0);
        if ($psychologistId <= 0) {
            header('Location: /admin');
            return;
        }

        $stmt = Database::connection()->prepare(
            "SELECT p.id, u.first_name, u.last_name, u.patronymic
             FROM psychologist_profiles p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ?"
        );
        $stmt->execute([$psychologistId]);
        $psychologist = $stmt->fetch();
        if (!$psychologist) {
            header('Location: /admin');
            return;
        }

        $stmt = Database::connection()->prepare(
            "SELECT id, slot_date, start_time, end_time, status, format, is_emergency_override
             FROM schedule_slots
             WHERE psychologist_id = ? AND slot_date >= CURDATE()
             ORDER BY slot_date, start_time"
        );
        $stmt->execute([$psychologistId]);
        $slots = $stmt->fetchAll();

        $maxHours = $this->maxHoursPerWeek($psychologistId);
        $currentWeekHours = $this->weeklyHours($psychologistId, date('Y-m-d'));

        require __DIR__ . '/../../templates/admin/psychologist_schedule.php';
    }

    /**
     * POST /admin/schedule
     * Добавить слот психологу.
     */
    public function addSlot(): void
    {
        Auth::requireRole('admin');

        $psychologistId = (int)($_POST['psychologist_id'] ?? 0);
        $date = $_POST['slot_date'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        $format = $_POST['format'] ?? 'individual';
        $emergencyOverride = isset($_POST['emergency_override']);

        if ($psychologistId <= 0 || !$date || !$start || !$end) {
            header('Location: /admin');
            return;
        }

        if (!in_array($format, ['individual', 'group', 'family'], true)) {
            $format = 'individual';
        }

        $durationHours = $this->hoursBetween($start, $end);
        if ($durationHours <= 0) {
            $_SESSION['schedule_error'] = 'Время окончания должно быть позже времени начала.';
            header('Location: /admin/schedule?psychologist_id=' . $psychologistId);
            return;
        }

        $existingHours = $this->weeklyHours($psychologistId, $date);
        $maxHours = $this->maxHoursPerWeek($psychologistId);

        if ($existingHours + $durationHours > $maxHours && !$emergencyOverride) {
            $_SESSION['schedule_error'] = sprintf(
                'Недельный лимит превышен: уже занято %.1fч из %.1fч. Отметьте «экстренный случай», чтобы добавить сверх лимита.',
                $existingHours,
                $maxHours
            );
            header('Location: /admin/schedule?psychologist_id=' . $psychologistId);
            return;
        }

        $isOverride = ($existingHours + $durationHours > $maxHours) ? 1 : 0;

        $stmt = Database::connection()->prepare(
            "INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status, is_emergency_override)
             VALUES (?, ?, ?, ?, ?, 'free', ?)"
        );
        $stmt->execute([$psychologistId, $date, $start, $end, $format, $isOverride]);

        header('Location: /admin/schedule?psychologist_id=' . $psychologistId);
    }

    /**
     * POST /admin/schedule/block
     */
    public function blockSlot(): void
    {
        Auth::requireRole('admin');

        $psychologistId = (int)($_POST['psychologist_id'] ?? 0);
        $slotId = (int)($_POST['slot_id'] ?? 0);
        if ($psychologistId <= 0 || $slotId <= 0) {
            header('Location: /admin');
            return;
        }

        $stmt = Database::connection()->prepare(
            "UPDATE schedule_slots SET status = 'blocked'
             WHERE id = ? AND psychologist_id = ? AND status = 'free'"
        );
        $stmt->execute([$slotId, $psychologistId]);
        header('Location: /admin/schedule?psychologist_id=' . $psychologistId);
    }

    /**
     * GET /admin/appointments
     * Список записей (для отмены/переноса).
     */
    public function appointments(): void
    {
        Auth::requireRole('admin');

        $stmt = Database::connection()->query(
            "SELECT a.id, a.status, a.request_comment,
                    s.slot_date, s.start_time,
                    pu.last_name AS psych_last_name, pu.first_name AS psych_first_name,
                    cu.last_name AS client_last_name, cu.first_name AS client_first_name, cu.group_or_dept, cu.phone, cu.email
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN psychologist_profiles pp ON pp.id = s.psychologist_id
             JOIN users pu ON pu.id = pp.user_id
             JOIN users cu ON cu.id = a.client_id
             ORDER BY s.slot_date DESC, s.start_time DESC
             LIMIT 200"
        );
        $appointments = $stmt->fetchAll();

        require __DIR__ . '/../../templates/admin/appointments_list.php';
    }

    /**
     * POST /admin/appointments/cancel
     */
    public function cancelAppointment(): void
    {
        Auth::requireRole('admin');

        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        if ($appointmentId <= 0) {
            header('Location: /admin/appointments');
            return;
        }

        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT a.id, a.slot_id, a.status, a.client_id, s.slot_date, s.start_time,
                    pu.last_name AS psych_last_name, pu.first_name AS psych_first_name
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN psychologist_profiles pp ON pp.id = s.psychologist_id
             JOIN users pu ON pu.id = pp.user_id
             WHERE a.id = ?"
        );
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch();

        if (!$appointment || $appointment['status'] !== 'active') {
            header('Location: /admin/appointments');
            return;
        }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE appointments SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?")
                ->execute([$appointmentId]);
            $db->prepare("UPDATE schedule_slots SET status = 'free' WHERE id = ?")
                ->execute([$appointment['slot_id']]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
        }

        // уведомление клиенту (заглушка)
        $clientRow = $db->prepare('SELECT id, email, last_name, first_name FROM users WHERE id = ?');
        $clientRow->execute([(int)$appointment['client_id']]);
        $client = $clientRow->fetch();
        if ($client) {
            NotificationDispatcher::default()->notify(
                $client,
                'Запись отменена администратором',
                sprintf(
                    'Ваша запись к психологу %s %s на %s в %s отменена.',
                    $appointment['psych_last_name'],
                    $appointment['psych_first_name'],
                    date('d.m.Y', strtotime($appointment['slot_date'])),
                    substr($appointment['start_time'], 0, 5)
                )
            );
        }

        header('Location: /admin/appointments');
    }

    /**
     * GET /admin/appointments/move?id=...
     * POST /admin/appointments/move
     */
    public function moveAppointmentForm(): void
    {
        Auth::requireRole('admin');
        $appointmentId = (int)($_GET['id'] ?? 0);
        if ($appointmentId <= 0) {
            header('Location: /admin/appointments');
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT a.id, a.status, a.client_id, a.slot_id,
                    s.psychologist_id, s.slot_date, s.start_time,
                    pu.last_name AS psych_last_name, pu.first_name AS psych_first_name,
                    cu.last_name AS client_last_name, cu.first_name AS client_first_name
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN psychologist_profiles pp ON pp.id = s.psychologist_id
             JOIN users pu ON pu.id = pp.user_id
             JOIN users cu ON cu.id = a.client_id
             WHERE a.id = ?"
        );
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch();

        if (!$appointment || $appointment['status'] !== 'active') {
            header('Location: /admin/appointments');
            return;
        }

        $slots = $db->prepare(
            "SELECT id, slot_date, start_time, end_time, format
             FROM schedule_slots
             WHERE psychologist_id = ? AND status = 'free' AND slot_date >= CURDATE()
             ORDER BY slot_date, start_time"
        );
        $slots->execute([(int)$appointment['psychologist_id']]);
        $freeSlots = $slots->fetchAll();

        require __DIR__ . '/../../templates/admin/appointment_move.php';
    }

    public function moveAppointment(): void
    {
        Auth::requireRole('admin');

        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $newSlotId = (int)($_POST['new_slot_id'] ?? 0);
        if ($appointmentId <= 0 || $newSlotId <= 0) {
            header('Location: /admin/appointments');
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT a.id, a.status, a.client_id, a.slot_id AS old_slot_id,
                    s.psychologist_id,
                    os.slot_date AS old_date, os.start_time AS old_time,
                    ns.slot_date AS new_date, ns.start_time AS new_time,
                    pu.last_name AS psych_last_name, pu.first_name AS psych_first_name
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN schedule_slots os ON os.id = a.slot_id
             JOIN schedule_slots ns ON ns.id = ?
             JOIN psychologist_profiles pp ON pp.id = s.psychologist_id
             JOIN users pu ON pu.id = pp.user_id
             WHERE a.id = ?"
        );
        $stmt->execute([$newSlotId, $appointmentId]);
        $row = $stmt->fetch();

        if (!$row || $row['status'] !== 'active') {
            header('Location: /admin/appointments');
            return;
        }

        // слот должен принадлежать тому же психологу и быть свободным
        $check = $db->prepare("SELECT psychologist_id, status FROM schedule_slots WHERE id = ?");
        $check->execute([$newSlotId]);
        $slotRow = $check->fetch();
        if (!$slotRow || $slotRow['status'] !== 'free' || (int)$slotRow['psychologist_id'] !== (int)$row['psychologist_id']) {
            header('Location: /admin/appointments/move?id=' . $appointmentId);
            return;
        }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE schedule_slots SET status = 'free' WHERE id = ?")->execute([(int)$row['old_slot_id']]);
            $db->prepare("UPDATE schedule_slots SET status = 'booked' WHERE id = ?")->execute([$newSlotId]);
            $db->prepare("UPDATE appointments SET slot_id = ? WHERE id = ?")->execute([$newSlotId, $appointmentId]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            header('Location: /admin/appointments/move?id=' . $appointmentId);
            return;
        }

        $clientRow = $db->prepare('SELECT id, email, last_name, first_name FROM users WHERE id = ?');
        $clientRow->execute([(int)$row['client_id']]);
        $client = $clientRow->fetch();
        if ($client) {
            NotificationDispatcher::default()->notify(
                $client,
                'Запись перенесена',
                sprintf(
                    'Ваша запись к психологу %s %s перенесена с %s %s на %s %s.',
                    $row['psych_last_name'],
                    $row['psych_first_name'],
                    date('d.m.Y', strtotime($row['old_date'])),
                    substr($row['old_time'], 0, 5),
                    date('d.m.Y', strtotime($row['new_date'])),
                    substr($row['new_time'], 0, 5)
                )
            );
        }

        header('Location: /admin/appointments');
    }

    private function hoursBetween(string $start, string $end): float
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        return ($endTs - $startTs) / 3600;
    }

    private function weeklyHours(int $psychologistId, string $aroundDate): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) / 3600 AS hours
             FROM schedule_slots
             WHERE psychologist_id = ?
               AND status IN ('free', 'booked')
               AND YEARWEEK(slot_date, 3) = YEARWEEK(?, 3)"
        );
        $stmt->execute([$psychologistId, $aroundDate]);
        return (float)$stmt->fetchColumn();
    }

    private function maxHoursPerWeek(int $psychologistId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT max_hours_per_week FROM psychologist_profiles WHERE id = ?"
        );
        $stmt->execute([$psychologistId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : 9.0;
    }

    /**
     * GET /admin/notifications
     * Заглушка-витрина: что бы отправилось по email/порталу, если бы
     * каналы были подключены по-настоящему. Реальной отправки нет,
     * это просто notification_log.
     */
    public function notifications(): void
    {
        Auth::requireRole('admin');

        $items = Database::connection()->query(
            "SELECT n.channel, n.subject, n.message, n.status, n.created_at,
                    u.last_name, u.first_name
             FROM notification_log n
             JOIN users u ON u.id = n.user_id
             ORDER BY n.created_at DESC
             LIMIT 50"
        )->fetchAll();

        require __DIR__ . '/../../templates/admin/notifications.php';
    }
}
