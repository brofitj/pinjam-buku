<?php

use App\Core\Database;

class CreateTransactionsTable
{
    public function up()
    {
        $db = Database::getInstance();

        $sql = "
            CREATE TABLE tbr_transactions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                transaction_code VARCHAR(100) NULL,
                borrow_date DATE NULL,
                due_date DATE NULL,
                return_date DATE NULL,
                status VARCHAR(50) NULL,
                fine_amount DECIMAL(15,2) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (user_id)
                    REFERENCES tbr_users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $db->exec($sql);
    }

    public function down()
    {
        $db = Database::getInstance();
        $db->exec("DROP TABLE IF EXISTS tbr_transactions");
    }
}