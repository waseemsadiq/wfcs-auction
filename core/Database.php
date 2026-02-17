<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;
    private static ?self $instance = null;

    private function __construct()
    {
        $config = require dirname(__DIR__) . '/config/database.php';

        $dsn = isset($config['socket']) && $config['socket']
            ? "mysql:unix_socket={$config['socket']};dbname={$config['database']};charset={$config['charset']}"
            : "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";

        $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        $this->pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute a query and return all rows.
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return the first row, or null.
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a statement (INSERT/UPDATE/DELETE) and return success.
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Return the last inserted auto-increment ID.
     */
    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Return the number of rows affected by the last statement.
     */
    public function rowCount(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Fetch a single scalar value from the first column of the first row.
     */
    public function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchColumn();
        return $result === false ? null : $result;
    }

    // Prevent cloning of the singleton
    private function __clone() {}
}
