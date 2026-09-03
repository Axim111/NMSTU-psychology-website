<?php

namespace App\Controllers;

use App\Core\Database;

class HomeController
{
    public function index(): void
    {
        $psychologists = [];
        $directions = [];
        try {
            $stmt = Database::connection()->query(
                "SELECT
                    p.id,
                    u.first_name, u.last_name, u.patronymic,
                    p.photo_path, p.bio,
                    GROUP_CONCAT(d.name SEPARATOR ', ') AS directions,
                    GROUP_CONCAT(d.id SEPARATOR ',') AS direction_ids
                 FROM psychologist_profiles p
                 JOIN users u ON u.id = p.user_id
                 LEFT JOIN psychologist_directions pd ON pd.psychologist_id = p.id
                 LEFT JOIN directions d ON d.id = pd.direction_id
                 GROUP BY p.id, u.first_name, u.last_name, u.patronymic
                 ORDER BY u.last_name"
            );
            $psychologists = $stmt->fetchAll();

            $dirStmt = Database::connection()->query("SELECT id, name FROM directions ORDER BY id");
            $directions = $dirStmt->fetchAll();
        } catch (\Throwable $e) {
            // На старте БД может быть ещё не создана — не роняем страницу.
        }

        require __DIR__ . '/../../templates/home.php';
    }
}
