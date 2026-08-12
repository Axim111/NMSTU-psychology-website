<?php

namespace App\Controllers;

use App\Core\Database;

class HomeController
{
    public function index(): void
    {
        $psychologists = [];
        try {
            $stmt = Database::connection()->query(
                "SELECT
                    p.id,
                    u.first_name, u.last_name, u.patronymic,
                    GROUP_CONCAT(d.name SEPARATOR ', ') AS directions
                 FROM psychologist_profiles p
                 JOIN users u ON u.id = p.user_id
                 LEFT JOIN psychologist_directions pd ON pd.psychologist_id = p.id
                 LEFT JOIN directions d ON d.id = pd.direction_id
                 GROUP BY p.id, u.first_name, u.last_name, u.patronymic
                 ORDER BY u.last_name"
            );
            $psychologists = $stmt->fetchAll();
        } catch (\Throwable $e) {
            // На старте БД может быть ещё не создана — не роняем страницу.
        }

        require __DIR__ . '/../../templates/home.php';
    }
}
