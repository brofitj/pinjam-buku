<?php

use App\Core\Database;

class CreateRolesTable
{
    public function up()
    {
        $db = Database::getInstance();

        $sql = "
            CREATE TABLE tbr_roles (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NULL,
                description TEXT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $db->exec($sql);
    }

    public function down()
    {
        $db = Database::getInstance();
        $db->exec("DROP TABLE IF EXISTS tbr_roles");
    }
}