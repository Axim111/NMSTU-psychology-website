<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifications\NotificationDispatcher;
use PDO;

class PsychologistController
{
    /**
     * GET /dashboard/profile
     * Профиль психолога: фото/описание/направления.
     */
    public function profile(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $db = Database::connection();
        $profileStmt = $db->prepare(
            "SELECT p.id, p.photo_path, p.bio, u.first_name, u.last_name, u.patronymic
             FROM psychologist_profiles p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ?"
        );
        $profileStmt->execute([$psychologistId]);
        $profile = $profileStmt->fetch();

        $directions = $db->query("SELECT id, name FROM directions ORDER BY id")->fetchAll();
        $selStmt = $db->prepare("SELECT direction_id FROM psychologist_directions WHERE psychologist_id = ?");
        $selStmt->execute([$psychologistId]);
        $selected = array_map('intval', $selStmt->fetchAll(PDO::FETCH_COLUMN));

        require __DIR__ . '/../../templates/dashboard/profile.php';
    }

    /**
     * POST /dashboard/profile
     */
    public function updateProfile(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $photoPath = trim($_POST['photo_path'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $selectedDirections = $_POST['directions'] ?? [];

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE psychologist_profiles SET photo_path = ?, bio = ? WHERE id = ?");
            $stmt->execute([$photoPath ?: null, $bio ?: null, $psychologistId]);

            $db->prepare("DELETE FROM psychologist_directions WHERE psychologist_id = ?")->execute([$psychologistId]);
            if (is_array($selectedDirections) && !empty($selectedDirections)) {
                $ins = $db->prepare("INSERT INTO psychologist_directions (psychologist_id, direction_id) VALUES (?, ?)");
                foreach ($selectedDirections as $dirId) {
                    $dirId = (int)$dirId;
                    if ($dirId > 0) $ins->execute([$psychologistId, $dirId]);
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
        }

        header('Location: /dashboard/profile');
    }
    /**
     * GET /dashboard
     * Список ближайших записей психолога.
     */
    public function dashboard(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $stmt = Database::connection()->prepare(
            "SELECT a.id, a.status, a.request_comment,
                    s.slot_date, s.start_time,
                    u.id AS client_id,
                    u.last_name, u.first_name, u.patronymic, u.group_or_dept, u.phone, u.email
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN users u ON u.id = a.client_id
             WHERE s.psychologist_id = ? AND a.status = 'active'
             ORDER BY s.slot_date, s.start_time"
        );
        $stmt->execute([$psychologistId]);
        $appointments = $stmt->fetchAll();

        require __DIR__ . '/../../templates/dashboard/psychologist_home.php';
    }

    /**
     * GET /dashboard/schedule
     * Своё расписание — свободные и занятые слоты по датам.
     */
    public function schedule(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

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

        require __DIR__ . '/../../templates/dashboard/psychologist_schedule.php';
    }

    /**
     * POST /dashboard/schedule
     * Добавление нового слота в расписание.
     * Считает недельную нагрузку (лимит по ТЗ — 9ч/неделю) и блокирует
     * добавление сверх лимита, если явно не отмечено "экстренный случай".
     */
    public function addSlot(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $date = $_POST['slot_date'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        $format = $_POST['format'] ?? 'individual';
        $emergencyOverride = isset($_POST['emergency_override']);

        if (!in_array($format, ['individual', 'group', 'family'], true)) {
            $format = 'individual';
        }

        if (!$date || !$start || !$end) {
            header('Location: /dashboard/schedule');
            return;
        }

        $durationHours = $this->hoursBetween($start, $end);
        if ($durationHours <= 0) {
            $_SESSION['schedule_error'] = 'Время окончания должно быть позже времени начала.';
            header('Location: /dashboard/schedule');
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
            header('Location: /dashboard/schedule');
            return;
        }

        $isOverride = ($existingHours + $durationHours > $maxHours) ? 1 : 0;

        $stmt = Database::connection()->prepare(
            "INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status, is_emergency_override)
             VALUES (?, ?, ?, ?, ?, 'free', ?)"
        );
        $stmt->execute([$psychologistId, $date, $start, $end, $format, $isOverride]);

        header('Location: /dashboard/schedule');
    }

    /**
     * GET /dashboard/clients
     * Список всех клиентов, когда-либо обращавшихся к этому психологу.
     */
    public function clients(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $stmt = Database::connection()->prepare(
            "SELECT u.id, u.last_name, u.first_name, u.patronymic, u.group_or_dept,
                    COUNT(a.id) AS visits_count,
                    MAX(s.slot_date) AS last_visit
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN users u ON u.id = a.client_id
             WHERE s.psychologist_id = ?
             GROUP BY u.id, u.last_name, u.first_name, u.patronymic, u.group_or_dept
             ORDER BY last_visit DESC"
        );
        $stmt->execute([$psychologistId]);
        $clients = $stmt->fetchAll();

        require __DIR__ . '/../../templates/dashboard/clients_list.php';
    }

    /**
     * GET /dashboard/clients/show?id=
     * История посещений конкретного клиента (прошлые и будущие) + заметки.
     */
    public function clientDetail(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();
        $clientId = (int)($_GET['id'] ?? 0);

        $stmtClient = Database::connection()->prepare(
            'SELECT id, last_name, first_name, patronymic, group_or_dept, phone FROM users WHERE id = ? AND role = "client"'
        );
        $stmtClient->execute([$clientId]);
        $client = $stmtClient->fetch();

        if (!$client) {
            http_response_code(404);
            echo 'Клиент не найден.';
            return;
        }

        $stmt = Database::connection()->prepare(
            "SELECT a.id, a.status, a.request_comment, s.slot_date, s.start_time
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             WHERE a.client_id = ? AND s.psychologist_id = ?
             ORDER BY s.slot_date DESC, s.start_time DESC"
        );
        $stmt->execute([$clientId, $psychologistId]);
        $visits = $stmt->fetchAll();

        // Заметки по каждой записи — отдельным запросом, чтобы не городить
        // GROUP_CONCAT ради MVP.
        $notesStmt = Database::connection()->prepare(
            'SELECT note_text, created_at FROM appointment_notes WHERE appointment_id = ? ORDER BY created_at'
        );
        foreach ($visits as &$visit) {
            $notesStmt->execute([$visit['id']]);
            $visit['notes'] = $notesStmt->fetchAll();
        }
        unset($visit);

        require __DIR__ . '/../../templates/dashboard/client_detail.php';
    }

    /**
     * POST /dashboard/notes
     * Заметка психолога к конкретной записи — видна только психологам.
     */
    public function addNote(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $clientId = (int)($_POST['client_id'] ?? 0);
        $noteText = trim($_POST['note_text'] ?? '');

        if ($noteText !== '') {
            // Проверяем, что запись действительно относится к этому психологу,
            // чтобы нельзя было оставить заметку к чужому клиенту.
            $stmt = Database::connection()->prepare(
                "SELECT a.id FROM appointments a
                 JOIN schedule_slots s ON s.id = a.slot_id
                 WHERE a.id = ? AND s.psychologist_id = ?"
            );
            $stmt->execute([$appointmentId, $psychologistId]);

            if ($stmt->fetch()) {
                $stmt = Database::connection()->prepare(
                    'INSERT INTO appointment_notes (appointment_id, note_text) VALUES (?, ?)'
                );
                $stmt->execute([$appointmentId, $noteText]);
            }
        }

        header('Location: /dashboard/clients/show?id=' . $clientId);
    }

    /**
     * POST /dashboard/appointments/cancel
     * Отмена записи психологом (без ограничения 24ч).
     */
    public function cancelAppointment(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        if ($appointmentId <= 0) {
            header('Location: /dashboard');
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT a.id, a.slot_id, a.status, a.client_id,
                    s.slot_date, s.start_time,
                    cu.email, cu.last_name AS client_last_name, cu.first_name AS client_first_name
             FROM appointments a
             JOIN schedule_slots s ON s.id = a.slot_id
             JOIN users cu ON cu.id = a.client_id
             WHERE a.id = ? AND s.psychologist_id = ?"
        );
        $stmt->execute([$appointmentId, $psychologistId]);
        $appointment = $stmt->fetch();

        if (!$appointment || $appointment['status'] !== 'active') {
            header('Location: /dashboard');
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

        $clientRow = $db->prepare('SELECT id, email, last_name, first_name FROM users WHERE id = ?');
        $clientRow->execute([(int)$appointment['client_id']]);
        $client = $clientRow->fetch();
        if ($client) {
            NotificationDispatcher::default()->notify(
                $client,
                'Запись отменена психологом',
                sprintf(
                    'Ваша запись на %s в %s отменена.',
                    date('d.m.Y', strtotime($appointment['slot_date'])),
                    substr($appointment['start_time'], 0, 5)
                )
            );
        }

        header('Location: /dashboard');
    }

    /**
     * POST /dashboard/message
     * Сообщение клиенту (уходит в уведомления как "почта/портал" заглушка).
     */
    public function messageClient(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $clientId = (int)($_POST['client_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if ($clientId <= 0 || $message === '') {
            header('Location: /dashboard/clients/show?id=' . $clientId);
            return;
        }

        $db = Database::connection();

        // Проверяем, что клиент вообще относится к этому психологу.
        // Если передали appointment_id — проверяем конкретную запись; если нет — достаточно факта обращения.
        if ($appointmentId > 0) {
            $stmt = $db->prepare(
                "SELECT a.id
                 FROM appointments a
                 JOIN schedule_slots s ON s.id = a.slot_id
                 WHERE a.id = ? AND a.client_id = ? AND s.psychologist_id = ?"
            );
            $stmt->execute([$appointmentId, $clientId, $psychologistId]);
            if (!$stmt->fetch()) {
                header('Location: /dashboard/clients/show?id=' . $clientId);
                return;
            }
        } else {
            $stmt = $db->prepare(
                "SELECT a.id
                 FROM appointments a
                 JOIN schedule_slots s ON s.id = a.slot_id
                 WHERE a.client_id = ? AND s.psychologist_id = ?
                 LIMIT 1"
            );
            $stmt->execute([$clientId, $psychologistId]);
            if (!$stmt->fetch()) {
                header('Location: /dashboard/clients/show?id=' . $clientId);
                return;
            }
        }

        $clientRow = $db->prepare('SELECT id, email, last_name, first_name FROM users WHERE id = ?');
        $clientRow->execute([$clientId]);
        $client = $clientRow->fetch();
        if ($client) {
            NotificationDispatcher::default()->notify(
                $client,
                'Сообщение от психолога',
                $message
            );
        }

        header('Location: /dashboard/clients/show?id=' . $clientId);
    }

    /**
     * POST /dashboard/schedule/block
     * Заблокировать слот (например, психолог заболел) — без брони.
     */
    public function blockSlot(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();
        $slotId = (int)($_POST['slot_id'] ?? 0);

        $stmt = Database::connection()->prepare(
            "UPDATE schedule_slots SET status = 'blocked'
             WHERE id = ? AND psychologist_id = ? AND status = 'free'"
        );
        $stmt->execute([$slotId, $psychologistId]);

        header('Location: /dashboard/schedule');
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
            'SELECT max_hours_per_week FROM psychologist_profiles WHERE id = ?'
        );
        $stmt->execute([$psychologistId]);
        return (float)($stmt->fetchColumn() ?: 9);
    }

    private function psychologistProfileId(): int
    {
        static $id = null;
        if ($id === null) {
            $stmt = Database::connection()->prepare(
                'SELECT id FROM psychologist_profiles WHERE user_id = ?'
            );
            $stmt->execute([Auth::id()]);
            $id = (int)($stmt->fetchColumn() ?: 0);
        }
        return $id;
    }
}
