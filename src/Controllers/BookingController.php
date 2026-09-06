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
     * Для ролей admin/psychologist показывает форму «Создать запись для клиента».
     */
    public function show(): void
    {
        $db = Database::connection();
        $psychologistId = (int) ($_GET['id'] ?? 0);
        $selectedDate = $_GET['date'] ?? null;

        $stmt = $db->prepare(
            "SELECT
                p.id,
                u.first_name, u.last_name, u.patronymic,
                p.photo_path, p.bio,
                GROUP_CONCAT(d.name SEPARATOR ', ') AS directions
             FROM psychologist_profiles p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN psychologist_directions pd ON pd.psychologist_id = p.id
             LEFT JOIN directions d ON d.id = pd.direction_id
             WHERE p.id = ?
             GROUP BY p.id, u.first_name, u.last_name, u.patronymic, p.photo_path, p.bio"
        );
        $stmt->execute([$psychologistId]);
        $psychologist = $stmt->fetch();

        if (!$psychologist) {
            http_response_code(404);
            echo 'Психолог не найден';
            return;
        }

        $isStaff = Auth::check() && in_array(Auth::role(), ['admin', 'psychologist'], true);
        $staffPsychologistProfileId = null;
        $allPsychologists = [];
        $freeSlotsForForm = [];

        if ($isStaff) {
            if (Auth::role() === 'psychologist') {
                $stmt = $db->prepare('SELECT id FROM psychologist_profiles WHERE user_id = ?');
                $stmt->execute([Auth::id()]);
                $staffPsychologistProfileId = (int) ($stmt->fetchColumn() ?: 0);
                $targetPsychId = $staffPsychologistProfileId;
            } else {
                $stmt = $db->query(
                    "SELECT p.id, u.last_name, u.first_name, u.patronymic
                     FROM psychologist_profiles p
                     JOIN users u ON u.id = p.user_id
                     ORDER BY u.last_name"
                );
                $allPsychologists = $stmt->fetchAll();
                $targetPsychId = $psychologistId;
            }

            if ($targetPsychId > 0) {
                $stmt = $db->prepare(
                    "SELECT id, slot_date, start_time, end_time, format
                     FROM schedule_slots
                     WHERE psychologist_id = ? AND status = 'free' AND slot_date >= CURDATE()
                     ORDER BY slot_date, start_time"
                );
                $stmt->execute([$targetPsychId]);
                $freeSlotsForForm = $stmt->fetchAll();
            }
        }

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
     * GET /api/psychologist/slots?id=1
     * JSON со свободными слотами (для модального выбора даты/времени).
     * Возвращаем группировку по датам, чтобы фронт мог быстро рисовать список
     * дат и тайм-слоты с цветовой маркировкой формата.
     */
    public function slotsApi(): void
    {
        $db = Database::connection();
        $psychologistId = (int) ($_GET['id'] ?? 0);

        if ($psychologistId <= 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'missing_id'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmt = $db->prepare(
            "SELECT id, slot_date, start_time, format
             FROM schedule_slots
             WHERE psychologist_id = ? AND status = 'free' AND slot_date >= CURDATE()
             ORDER BY slot_date, start_time"
        );
        $stmt->execute([$psychologistId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byDate = [];
        foreach ($rows as $r) {
            $date = $r['slot_date'];
            if (!isset($byDate[$date])) {
                $byDate[$date] = [
                    'date' => $date,
                    'slots' => [],
                ];
            }
            $byDate[$date]['slots'][] = [
                'id' => (int) $r['id'],
                'start_time' => $r['start_time'],
                'format' => $r['format'],
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['dates' => array_values($byDate)], JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /book?slot_id=5
     * Форма с данными клиента для выбранного слота.
     */
    public function bookForm(): void
    {
        $slotId = (int) ($_GET['slot_id'] ?? 0);
        $isPartial = (string) ($_GET['partial'] ?? '') === '1';
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
                'SELECT last_name, first_name, patronymic, group_or_dept, phone, email FROM users WHERE id = ?'
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
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $consent = (string) ($_POST['consent'] ?? '');

        $slot = $this->fetchSlotWithPsychologist($slotId);

        if (!$slot || $slot['status'] !== 'free') {
            http_response_code(409);
            echo 'Этот слот больше не доступен — возможно, его уже забронировали.';
            return;
        }

        if ($consent !== '1') {
            http_response_code(422);
            echo 'Нужно согласие на обработку персональных данных.';
            return;
        }

        $db = Database::connection();
        $isLoggedInClient = Auth::check() && Auth::role() === 'client';

        if (!$isLoggedInClient) {
            $lastName = trim($_POST['last_name'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $group = trim($_POST['group_or_dept'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');

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
                $emailToSave = ($email ?? '') !== '' ? $email : null;

                try {
                    $stmt = $db->prepare(
                        "INSERT INTO users (role, last_name, first_name, group_or_dept, phone, email)
                         VALUES ('client', ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$lastName, $firstName, $group, $phone, $emailToSave]);
                    $clientId = (int) $db->lastInsertId();
                } catch (\Throwable $e) {
                    // Если email уже занят — создаём запись без email (не мешаем записи на приём).
                    $stmt = $db->prepare(
                        "INSERT INTO users (role, last_name, first_name, group_or_dept, phone, email)
                         VALUES ('client', ?, ?, ?, ?, NULL)"
                    );
                    $stmt->execute([$lastName, $firstName, $group, $phone]);
                    $clientId = (int) $db->lastInsertId();
                }
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

    /**
     * POST /book-for-client
     * Создание записи психологом или админом для клиента (п.2.1 ТЗ).
     * - психолог может записать только к себе;
     * - админ может записать к любому психологу.
     */
    public function storeForClient(): void
    {
        Auth::requireRole('admin', 'psychologist');

        $db = Database::connection();
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $psychologistIdForm = (int) ($_POST['psychologist_id'] ?? 0);
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $patronymic = trim($_POST['patronymic'] ?? '');
        $group = trim($_POST['group_or_dept'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $contactLink = trim($_POST['contact_link'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        $slot = $this->fetchSlotWithPsychologist($slotId);
        if (!$slot || $slot['status'] !== 'free') {
            http_response_code(409);
            echo 'Выбранный слот больше не доступен. Обновите страницу и попробуйте снова.';
            return;
        }

        $currentRole = Auth::role();
        if ($currentRole === 'psychologist') {
            $stmt = $db->prepare('SELECT id FROM psychologist_profiles WHERE user_id = ?');
            $stmt->execute([Auth::id()]);
            $myProfileId = (int) ($stmt->fetchColumn() ?: 0);
            if ((int) $slot['psychologist_id'] !== $myProfileId) {
                http_response_code(403);
                echo 'Вы можете записывать клиентов только к себе.';
                return;
            }
            $targetPsychId = $myProfileId;
        } else {
            $targetPsychId = $psychologistIdForm > 0 ? $psychologistIdForm : (int) $slot['psychologist_id'];
            if ((int) $slot['psychologist_id'] !== $targetPsychId) {
                http_response_code(422);
                echo 'Слот принадлежит другому психологу.';
                return;
            }
        }

        if ($lastName === '' || $firstName === '' || $phone === '') {
            http_response_code(422);
            echo 'Заполните обязательные поля: фамилия, имя, телефон.';
            return;
        }

        $db->beginTransaction();
        try {
            $clientId = null;

            $stmt = $db->prepare(
                "SELECT id FROM users
                 WHERE role = 'client' AND last_name = ? AND first_name = ? AND phone = ?
                 LIMIT 1"
            );
            $stmt->execute([$lastName, $firstName, $phone]);
            $existing = $stmt->fetch();
            if ($existing) {
                $clientId = (int) $existing['id'];
                $upd = $db->prepare(
                    "UPDATE users SET
                        patronymic = COALESCE(NULLIF(?, ''), patronymic),
                        group_or_dept = COALESCE(NULLIF(?, ''), group_or_dept),
                        contact_link = COALESCE(NULLIF(?, ''), contact_link),
                        email = COALESCE(NULLIF(?, ''), email)
                     WHERE id = ?"
                );
                $upd->execute([$patronymic, $group, $contactLink, $email, $clientId]);
            }

            if (!$clientId) {
                try {
                    $stmt = $db->prepare(
                        "INSERT INTO users (role, last_name, first_name, patronymic, group_or_dept, phone, contact_link, email)
                         VALUES ('client', ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $lastName,
                        $firstName,
                        $patronymic !== '' ? $patronymic : null,
                        $group !== '' ? $group : null,
                        $phone,
                        $contactLink !== '' ? $contactLink : null,
                        $email !== '' ? $email : null,
                    ]);
                    $clientId = (int) $db->lastInsertId();
                } catch (\Throwable $e) {
                    $stmt = $db->prepare(
                        "INSERT INTO users (role, last_name, first_name, patronymic, group_or_dept, phone, contact_link, email)
                         VALUES ('client', ?, ?, ?, ?, ?, ?, NULL)"
                    );
                    $stmt->execute([
                        $lastName,
                        $firstName,
                        $patronymic !== '' ? $patronymic : null,
                        $group !== '' ? $group : null,
                        $phone,
                        $contactLink !== '' ? $contactLink : null,
                    ]);
                    $clientId = (int) $db->lastInsertId();
                }
            }

            $stmt = $db->prepare(
                "INSERT INTO appointments (slot_id, client_id, request_comment) VALUES (?, ?, ?)"
            );
            $stmt->execute([$slotId, $clientId, $comment !== '' ? $comment : null]);

            $stmt = $db->prepare("UPDATE schedule_slots SET status = 'booked' WHERE id = ?");
            $stmt->execute([$slotId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            echo 'Не получилось сохранить запись: ' . htmlspecialchars($e->getMessage());
            return;
        }

        $clientRow = $db->prepare(
            'SELECT id, email, last_name, first_name FROM users WHERE id = ?'
        );
        $clientRow->execute([$clientId]);
        $client = $clientRow->fetch();
        if ($client) {
            $psychFullName = trim($slot['last_name'] . ' ' . $slot['first_name'] . ' ' . ($slot['patronymic'] ?? ''));
            NotificationDispatcher::default()->notify(
                $client,
                'Вас записали на приём к психологу',
                sprintf(
                    'Вы записаны к психологу %s на %s в %s.',
                    $psychFullName,
                    date('d.m.Y', strtotime($slot['slot_date'])),
                    substr($slot['start_time'], 0, 5)
                )
            );
        }

        $redirect = '/psychologist?id=' . (int) $targetPsychId;
        header('Location: ' . $redirect);
    }

    private function fetchSlotWithPsychologist(int $slotId): array|false
    {
        $stmt = Database::connection()->prepare(
            "SELECT s.id, s.slot_date, s.start_time, s.end_time, s.format, s.status, s.psychologist_id,
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
