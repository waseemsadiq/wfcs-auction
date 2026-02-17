<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class CategoryRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Return all categories ordered by name.
     */
    public function all(): array
    {
        return $this->db->query(
            'SELECT * FROM categories ORDER BY name ASC'
        );
    }

    /**
     * Find a category by slug.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM categories WHERE slug = ?',
            [$slug]
        );
    }

    /**
     * Find a category by id.
     */
    public function findById(int $id): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM categories WHERE id = ?',
            [$id]
        );
    }
}
