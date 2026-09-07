<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

/**
 * Объявления и информационные памятки Центра.
 * Управление доступно роли admin; психолог в системе является admin.
 */
class AnnouncementController
{
    public function index(): void
    {
        $db = Database::connection();
        $events = $db->query(
            "SELECT id, title, content, event_date FROM announcements
             WHERE kind = 'event' ORDER BY event_date IS NULL, event_date DESC"
        )->fetchAll();
        $infoPages = $db->query(
            "SELECT id, title, content FROM announcements
             WHERE kind = 'info' ORDER BY created_at DESC"
        )->fetchAll();

        require __DIR__ . '/../../templates/announcements.php';
    }

    public function manage(): void
    {
        Auth::requireRole('admin');

        $db = Database::connection();
        $items = $db->query(
            "SELECT id, kind, title, content, event_date, created_at FROM announcements ORDER BY created_at DESC"
        )->fetchAll();

        require __DIR__ . '/../../templates/dashboard/announcements_manage.php';
    }

    public function create(): void
    {
        Auth::requireRole('admin');

        $kind = ($_POST['kind'] ?? 'event') === 'info' ? 'info' : 'event';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');

        if ($title !== '' && $content !== '') {
            $stmt = Database::connection()->prepare(
                "INSERT INTO announcements (kind, title, content, event_date, created_by)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$kind, $title, $content, $eventDate ?: null, Auth::id()]);
        }

        header('Location: /dashboard/announcements');
    }

    public function delete(): void
    {
        Auth::requireRole('admin');
        $id = (int)($_POST['id'] ?? 0);

        Database::connection()
            ->prepare('DELETE FROM announcements WHERE id = ?')
            ->execute([$id]);

        header('Location: /dashboard/announcements');
    }
}
