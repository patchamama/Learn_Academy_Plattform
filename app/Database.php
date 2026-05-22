<?php

namespace LearnAcademy\App;

class Database
{
    private static ?self $instance = null;
    private \PDO $pdo;

    private function __construct(string $dbPath)
    {
        $this->pdo = new \PDO('sqlite:' . $dbPath, null, null, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $dbPath = defined('DB_PATH') ? DB_PATH : __DIR__ . '/../database/app.sqlite';
            self::$instance = new self($dbPath);
        }
        return self::$instance;
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Run schema migrations from database/schema.sql if tables don't exist yet.
     */
    public function migrate(): void
    {
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaFile)) return;

        $sql = file_get_contents($schemaFile);
        // Execute each statement (split on ; followed by newline)
        $statements = preg_split('/;\s*\n/', $sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt !== '' && !str_starts_with($stmt, '--')) {
                try {
                    $this->pdo->exec($stmt);
                } catch (\PDOException) {
                    // Ignore "already exists" errors during migration
                }
            }
        }
    }
}
