<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

/**
 * kind='event' — групповые мероприятия/объявления Центра (видны всем).
 * kind='info'  — правила посещения, отмены записи, экстренная помощь (видны всем).
 * Создавать/редактировать может психолог или админ (по ТЗ это вносит психолог).
 */
class AnnouncementController
{
    /**
     * GET /announcements
     * Публичная страница — список мероприятий + информационные памятки.
     */
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

    /**
     * GET /dashboard/announcements
     * Управление — доступно психологу и админу.
     */
    public function manage(): void
    {
        Auth::requireRole('psychologist', 'admin');

        $db = Database::connection();
        $items = $db->query(
            "SELECT id, kind, title, content, event_date, created_at FROM announcements ORDER BY created_at DESC"
        )->fetchAll();

        require __DIR__ . '/../../templates/dashboard/announcements_manage.php';
    }

    /**
     * POST /dashboard/announcements
     */
    public function create(): void
    {
        Auth::requireRole('psychologist', 'admin');

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

    /**
     * POST /dashboard/announcements/delete
     */
    public function delete(): void
    {
        Auth::requireRole('psychologist', 'admin');
        $id = (int)($_POST['id'] ?? 0);

        Database::connection()
            ->prepare('DELETE FROM announcements WHERE id = ?')
            ->execute([$id]);

        header('Location: /dashboard/announcements');
    }
}
