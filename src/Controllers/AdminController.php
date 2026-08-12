<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

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
            "SELECT p.id, u.last_name, u.first_name, u.patronymic, u.email,
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

        if ($lastName === '' || $firstName === '' || $email === '' || $password === '') {
            $error = 'Заполните фамилию, имя, email и пароль.';
            require __DIR__ . '/../../templates/admin/new_psychologist.php';
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO users (role, last_name, first_name, patronymic, email, password_hash)
                 VALUES ('psychologist', ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $lastName, $firstName, $patronymic ?: null, $email,
                password_hash($password, PASSWORD_BCRYPT),
            ]);
            $userId = (int)$db->lastInsertId();

            $stmt = $db->prepare(
                "INSERT INTO psychologist_profiles (user_id, bio) VALUES (?, ?)"
            );
            $stmt->execute([$userId, $bio ?: null]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $error = 'Не получилось создать психолога — возможно, такой email уже есть.';
            require __DIR__ . '/../../templates/admin/new_psychologist.php';
            return;
        }

        header('Location: /admin');
    }
}
