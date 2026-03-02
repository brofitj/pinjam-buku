<?php

namespace App\Core;

use PDO;

class Migration
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->createMigrationsTableIfNotExists();
    }

    private function createMigrationsTableIfNotExists()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->db->exec($sql);
    }

    public function run(string $className, string $migrationName)
    {
        $migration = new $className;

        $migration->up();

        $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migrationName]);
    }

    public function migrated(string $migrationName): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migrationName]);

        return $stmt->fetchColumn() > 0;
    }
}