<?php

use App\Core\Database;

class CreateBooksTable
{
    public function up()
    {
        $db = Database::getInstance();

        $sql = "
            CREATE TABLE tbr_books (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                book_code VARCHAR(100) NULL,
                title VARCHAR(255) NULL,
                author VARCHAR(255) NULL,
                publisher VARCHAR(255) NULL,
                publication_year YEAR NULL,
                isbn VARCHAR(100) NULL,
                stock INT NULL,
                cover_image VARCHAR(255) NULL,
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
        $db->exec("DROP TABLE IF EXISTS tbr_books");
    }
}