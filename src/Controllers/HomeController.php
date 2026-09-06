<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

class HomeController
{
    public function index(): void
    {
        $directionId = isset($_GET['direction']) ? (int) $_GET['direction'] : null;
        $search = trim($_GET['q'] ?? '');

        $directions = [];
        $psychologists = [];

        $isPsychologist = Auth::check() && Auth::role() === 'psychologist';
        $currentPsychologist = null;
        $myProfileId = null;

        if ($isPsychologist) {
            try {
                $db = Database::connection();
                $stmt = $db->prepare(
                    "SELECT p.id AS profile_id,
                            u.first_name, u.last_name, u.patronymic
                     FROM psychologist_profiles p
                     JOIN users u ON u.id = p.user_id
                     WHERE p.user_id = ?"
                );
                $stmt->execute([Auth::id()]);
                $currentPsychologist = $stmt->fetch();
                if ($currentPsychologist) {
                    $myProfileId = (int) $currentPsychologist['profile_id'];
                    header('Location: /psychologist?id=' . $myProfileId);
                    return;
                }
            } catch (\Throwable $e) {
                $currentPsychologist = null;
            }
        }

        if (!$isPsychologist) {
            try {
                $directions = Database::connection()
                    ->query('SELECT id, name FROM directions ORDER BY name')
                    ->fetchAll();

                $where = [];
                $params = [];

                if ($directionId) {
                    $where[] = 'p.id IN (SELECT psychologist_id FROM psychologist_directions WHERE direction_id = ?)';
                    $params[] = $directionId;
                }

                if ($search !== '') {
                    $where[] = "CONCAT_WS(' ', u.last_name, u.first_name, u.patronymic) LIKE ?";
                    $params[] = '%' . $search . '%';
                }

                $sql = "SELECT
                            p.id,
                            u.first_name, u.last_name, u.patronymic,
                            p.photo_path, p.bio,
                            GROUP_CONCAT(d.name SEPARATOR ', ') AS directions,
                            GROUP_CONCAT(d.id SEPARATOR ',') AS direction_ids
                        FROM psychologist_profiles p
                        JOIN users u ON u.id = p.user_id
                        LEFT JOIN psychologist_directions pd ON pd.psychologist_id = p.id
                        LEFT JOIN directions d ON d.id = pd.direction_id";
                if ($where) {
                    $sql .= ' WHERE ' . implode(' AND ', $where);
                }
                $sql .= ' GROUP BY p.id, u.first_name, u.last_name, u.patronymic, p.photo_path, p.bio ORDER BY u.last_name';

                $stmt = Database::connection()->prepare($sql);
                $stmt->execute($params);
                $psychologists = $stmt->fetchAll();
            } catch (\Throwable $e) {
                // На старте БД может быть ещё не создана — не роняем страницу.
            }
        }

        require __DIR__ . '/../../templates/home.php';
    }
}
