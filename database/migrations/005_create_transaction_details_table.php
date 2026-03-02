<?php

use App\Core\Database;

class CreateTransactionDetailsTable
{
    public function up()
    {
        $db = Database::getInstance();

        $sql = "
            CREATE TABLE tbr_transaction_details (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                transaction_id BIGINT UNSIGNED NOT NULL,
                book_id BIGINT UNSIGNED NOT NULL,
                quantity INT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (transaction_id) 
                    REFERENCES tbr_transactions(id) 
                    ON DELETE CASCADE,
                FOREIGN KEY (book_id) 
                    REFERENCES tbr_books(id) 
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $db->exec($sql);
    }

    public function down()
    {
        $db = Database::getInstance();
        $db->exec("DROP TABLE IF EXISTS tbr_transaction_details");
    }
}