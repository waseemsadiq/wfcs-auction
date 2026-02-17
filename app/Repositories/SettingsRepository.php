<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class SettingsRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get a setting value by key. Returns null if not found.
     */
    public function get(string $key): ?string
    {
        $row = $this->db->queryOne(
            'SELECT value FROM settings WHERE key_name = ?',
            [$key]
        );

        return $row !== null ? (string)$row['value'] : null;
    }

    /**
     * Set a setting value. Inserts or updates (upsert).
     */
    public function set(string $key, string $value): void
    {
        $this->db->execute(
            'INSERT INTO settings (key_name, value, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE value = ?, updated_at = NOW()',
            [$key, $value, $value]
        );
    }

    /**
     * Get all settings as a key => value array.
     */
    public function all(): array
    {
        $rows   = $this->db->query('SELECT key_name, value FROM settings');
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key_name']] = $row['value'];
        }
        return $result;
    }

    /**
     * Delete a setting by key.
     */
    public function delete(string $key): void
    {
        $this->db->execute(
            'DELETE FROM settings WHERE key_name = ?',
            [$key]
        );
    }
}
