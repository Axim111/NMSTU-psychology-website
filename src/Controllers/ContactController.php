<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

/**
 * Раздел "Ссылки" из ТЗ: телефон экстренной помощи, контакты ЦСИ «Пирамиды»,
 * ВК кафедры психологии, ВК психологов и т.п. Управляет админ — контакты
 * общие для всего центра, не привязаны к конкретному психологу.
 */
class ContactController
{
    /**
     * GET /contacts
     * Публичная страница со списком контактов.
     */
    public function index(): void
    {
        $contacts = Database::connection()
            ->query('SELECT id, title, value, type FROM contacts ORDER BY id')
            ->fetchAll();

        require __DIR__ . '/../../templates/contacts.php';
    }

    /**
     * GET /admin/contacts
     */
    public function manage(): void
    {
        Auth::requireRole('admin');

        $contacts = Database::connection()
            ->query('SELECT id, title, value, type FROM contacts ORDER BY id')
            ->fetchAll();

        require __DIR__ . '/../../templates/admin/contacts_manage.php';
    }

    /**
     * POST /admin/contacts
     */
    public function create(): void
    {
        Auth::requireRole('admin');

        $title = trim($_POST['title'] ?? '');
        $value = trim($_POST['value'] ?? '');
        $type = $_POST['type'] ?? 'text';
        if (!in_array($type, ['phone', 'link', 'text'], true)) {
            $type = 'text';
        }

        if ($title !== '' && $value !== '') {
            $stmt = Database::connection()->prepare(
                'INSERT INTO contacts (title, value, type) VALUES (?, ?, ?)'
            );
            $stmt->execute([$title, $value, $type]);
        }

        header('Location: /admin/contacts');
    }

    /**
     * POST /admin/contacts/delete
     */
    public function delete(): void
    {
        Auth::requireRole('admin');
        $id = (int)($_POST['id'] ?? 0);

        Database::connection()
            ->prepare('DELETE FROM contacts WHERE id = ?')
            ->execute([$id]);

        header('Location: /admin/contacts');
    }
}
