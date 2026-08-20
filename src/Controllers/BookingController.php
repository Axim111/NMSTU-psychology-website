<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifications\NotificationDispatcher;
use PDO;

class BookingController
{
    /**
     * GET /psychologist?id=1&date=2026-08-12
     * Показывает карточку психолога, список дат со свободными слотами,
     * а если дата выбрана — ещё и время на эту дату.
     */
    public function show(): void
    {
        $db = Database::connection();
        $psychologistId = (int)($_GET['id'] ?? 0);
        $selectedDate = $_GET['date'] ?? null;

        $stmt = $db->prepare(
            "SELECT p.id, u.first_name, u.last_name, u.patronymic
             FROM psychologist_profiles p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ?"
        );
        $stmt->execute([$psychologistId]);
        $psychologist = $stmt->fetch();

        if (!$psychologist) {
            http_response_code(404);
            echo 'Психолог не найден';
            return;
        }

        // Уникальные даты, на которые есть хотя бы один свободный слот
        $stmt = $db->prepare(
            "SELECT DISTINCT slot_date
             FROM schedule_slots
             WHERE psychologist_id = ? AND status = 'free' AND slot_date >= CURDATE()
             ORDER BY slot_date"
        );
        $stmt->execute([$psychologistId]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $times = [];
        if ($selectedDate) {
            $stmt = $db->prepare(
                "SELECT id, start_time
                 FROM schedule_slots
                 WHERE psychologist_id = ? AND slot_date = ? AND status = 'free'
                 ORDER BY start_time"
            );
            $stmt->execute([$psychologistId, $selectedDate]);
            $times = $stmt->fetchAll();
        }

        require __DIR__ . '/../../templates/psychologist.php';
    }

    /**
     * GET /book?slot_id=5
     * Форма с данными клиента для выбранного слота.
     */
    public function bookForm(): void
    {
        $slotId = (int)($_GET['slot_id'] ?? 0);
        $slot = $this->fetchSlotWithPsychologist($slotId);

        if (!$slot || $slot['status'] !== 'free') {
            http_response_code(404);
            echo 'Этот слот больше не доступен — возможно, его уже забронировали.';
            return;
        }

        // Если это залогиненный клиент (кабинет студента), подтягиваем его
        // данные, чтобы форма не спрашивала ФИО/телефон заново.
        $loggedInClient = null;
        if (Auth::check() && Auth::role() === 'client') {
            $stmt = Database::connection()->prepare(
                'SELECT last_name, first_name, patronymic, group_or_dept, phone FROM users WHERE id = ?'
            );
            $stmt->execute([Auth::id()]);
            $loggedInClient = $stmt->fetch();
        }

        require __DIR__ . '/../../templates/booking_form.php';
    }

    /**
     * POST /book
     * Создаёт запись на приём. Если клиент залогинен в кабинете — запись
     * привязывается к его аккаунту. Если нет (гостевой сценарий, пока не
     * подключён SSO портала) — заводит нового клиента по ФИО/телефону,
     * как раньше.
     */
    public function store(): void
    {
        $slotId = (int)($_POST['slot_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        $slot = $this->fetchSlotWithPsychologist($slotId);

        if (!$slot || $slot['status'] !== 'free') {
            http_response_code(409);
            echo 'Этот слот больше не доступен — возможно, его уже забронировали.';
            return;
        }

        $db = Database::connection();
        $isLoggedInClient = Auth::check() && Auth::role() === 'client';

        if (!$isLoggedInClient) {
            $lastName = trim($_POST['last_name'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $group = trim($_POST['group_or_dept'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($lastName === '' || $firstName === '' || $phone === '') {
                http_response_code(422);
                echo 'Заполните обязательные поля (фамилия, имя, телефон) и вернитесь назад.';
                return;
            }
        }

        $db->beginTransaction();

        try {
            if ($isLoggedInClient) {
                $clientId = Auth::id();
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO users (role, last_name, first_name, group_or_dept, phone)
                     VALUES ('client', ?, ?, ?, ?)"
                );
                $stmt->execute([$lastName, $firstName, $group, $phone]);
                $clientId = (int)$db->lastInsertId();
            }

            $stmt = $db->prepare(
                "INSERT INTO appointments (slot_id, client_id, request_comment)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$slotId, $clientId, $comment ?: null]);

            $stmt = $db->prepare("UPDATE schedule_slots SET status = 'booked' WHERE id = ?");
            $stmt->execute([$slotId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            echo 'Не получилось сохранить запись, попробуйте ещё раз.';
            return;
        }

        // Оповещение клиента о записи (заглушка — см. src/Core/Notifications/).
        $clientRow = $db->prepare('SELECT id, email, last_name, first_name FROM users WHERE id = ?');
        $clientRow->execute([$clientId]);
        $client = $clientRow->fetch();
        if ($client) {
            $psychFullName = trim($slot['last_name'] . ' ' . $slot['first_name'] . ' ' . ($slot['patronymic'] ?? ''));
            NotificationDispatcher::default()->notify(
                $client,
                'Запись на приём подтверждена',
                sprintf(
                    'Вы записаны к психологу %s на %s в %s.',
                    $psychFullName,
                    date('d.m.Y', strtotime($slot['slot_date'])),
                    substr($slot['start_time'], 0, 5)
                )
            );
        }

        require __DIR__ . '/../../templates/booking_confirmation.php';
    }

    private function fetchSlotWithPsychologist(int $slotId): array|false
    {
        $stmt = Database::connection()->prepare(
            "SELECT s.id, s.slot_date, s.start_time, s.status, s.psychologist_id,
                    u.first_name, u.last_name, u.patronymic
             FROM schedule_slots s
             JOIN psychologist_profiles p ON p.id = s.psychologist_id
             JOIN users u ON u.id = p.user_id
             WHERE s.id = ?"
        );
        $stmt->execute([$slotId]);
        return $stmt->fetch();
    }
}
