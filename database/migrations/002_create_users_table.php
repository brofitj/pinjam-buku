<?php

use App\Core\Database;

class CreateUsersTable
{
    public function up()
    {
        $db = Database::getInstance();

        $sql = "
            CREATE TABLE tbr_users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NULL,
                avatar VARCHAR(255) NULL,
                gender enum('male', 'female') NULL,
                phone VARCHAR(50) NULL,
                address VARCHAR(255) NULL,
                join_date DATE NULL,

                role_id BIGINT UNSIGNED NOT NULL,
                email VARCHAR(150) NULL,
                email_verified_at TIMESTAMP NULL,
                username VARCHAR(100) NULL,
                password VARCHAR(255) NULL,
                status enum('active', 'inactive') NULL,          

                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (role_id) REFERENCES tbr_roles(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $db->exec($sql);
    }

    public function down()
    {
        $db = Database::getInstance();
        $db->exec("DROP TABLE IF EXISTS tbr_users");
    }
}