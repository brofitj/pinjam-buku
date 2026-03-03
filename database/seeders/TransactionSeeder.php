<?php

use App\Core\Database;

class TransactionSeeder
{
    public function run()
    {
        $db = Database::getInstance();

        $countStmt = $db->query('SELECT COUNT(*) FROM tbr_transactions');
        $existingCount = (int)$countStmt->fetchColumn();
        $targetCount = 5;

        if ($existingCount >= $targetCount) {
            echo "TransactionSeeder skipped. Existing transactions: {$existingCount}\n";
            return;
        }

        $memberStmt = $db->query(
            "SELECT u.id
             FROM tbr_users u
             INNER JOIN tbr_roles r ON r.id = u.role_id
             WHERE r.name = 'member'
             ORDER BY u.id ASC"
        );
        $memberIds = $memberStmt->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($memberIds)) {
            echo "TransactionSeeder skipped. No member found.\n";
            return;
        }

        $bookStmt = $db->query('SELECT id FROM tbr_books ORDER BY id ASC');
        $bookIds = $bookStmt->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($bookIds)) {
            echo "TransactionSeeder skipped. No book found.\n";
            return;
        }

        $toInsert = $targetCount - $existingCount;
        $startNo = $existingCount + 1;

        $insertTransactionStmt = $db->prepare(
            "INSERT INTO tbr_transactions
                (user_id, transaction_code, borrow_date, due_date, return_date, status, fine_amount, created_at, updated_at)
             VALUES
                (:user_id, :transaction_code, :borrow_date, :due_date, :return_date, :status, :fine_amount, NOW(), NOW())"
        );

        $insertDetailStmt = $db->prepare(
            "INSERT INTO tbr_transaction_details
                (transaction_id, book_id, quantity, created_at, updated_at)
             VALUES
                (:transaction_id, :book_id, :quantity, NOW(), NOW())"
        );

        for ($i = 0; $i < $toInsert; $i++) {
            $no = $startNo + $i;
            $memberId = (int)$memberIds[$i % count($memberIds)];

            $borrowDate = date('Y-m-d', strtotime('-' . (10 - $i) . ' days'));
            $dueDate = date('Y-m-d', strtotime($borrowDate . ' +7 days'));

            $status = 'borrowed';
            $returnDate = null;
            $fineAmount = 0;

            if ($i % 3 === 1) {
                $status = 'returned';
                $returnDate = date('Y-m-d', strtotime($dueDate . ' -1 day'));
            } elseif ($i % 3 === 2) {
                $status = 'overdue';
                $returnDate = null;
                $fineAmount = 10000;
            }

            $transactionCode = 'TRX' . date('Ymd') . str_pad((string)$no, 4, '0', STR_PAD_LEFT);

            $insertTransactionStmt->execute([
                ':user_id' => $memberId,
                ':transaction_code' => $transactionCode,
                ':borrow_date' => $borrowDate,
                ':due_date' => $dueDate,
                ':return_date' => $returnDate,
                ':status' => $status,
                ':fine_amount' => $fineAmount,
            ]);

            $transactionId = (int)$db->lastInsertId();

            $detailCount = 1 + ($i % 2);
            for ($d = 0; $d < $detailCount; $d++) {
                $bookId = (int)$bookIds[($i + $d) % count($bookIds)];
                $quantity = 1;

                $insertDetailStmt->execute([
                    ':transaction_id' => $transactionId,
                    ':book_id' => $bookId,
                    ':quantity' => $quantity,
                ]);
            }
        }

        echo "TransactionSeeder inserted {$toInsert} transactions. Total transactions now at least {$targetCount}.\n";
    }
}

