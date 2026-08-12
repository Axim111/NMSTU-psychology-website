<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

class PsychologistController
{
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
                    u.last_name, u.first_name, u.patronymic, u.group_or_dept, u.phone
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
            "SELECT id, slot_date, start_time, end_time, status
             FROM schedule_slots
             WHERE psychologist_id = ? AND slot_date >= CURDATE()
             ORDER BY slot_date, start_time"
        );
        $stmt->execute([$psychologistId]);
        $slots = $stmt->fetchAll();

        require __DIR__ . '/../../templates/dashboard/psychologist_schedule.php';
    }

    /**
     * POST /dashboard/schedule
     * Добавление нового слота в расписание.
     */
    public function addSlot(): void
    {
        Auth::requireRole('psychologist');
        $psychologistId = $this->psychologistProfileId();

        $date = $_POST['slot_date'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';

        if ($date && $start && $end) {
            $stmt = Database::connection()->prepare(
                "INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
                 VALUES (?, ?, ?, ?, 'individual', 'free')"
            );
            $stmt->execute([$psychologistId, $date, $start, $end]);
        }

        header('Location: /dashboard/schedule');
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
