<?php

namespace App\Controllers;

use App\Core\Database;

class TransactionController
{
    private function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user']);
    }

    /**
     * Return list of transactions as JSON.
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Unauthenticated']);
            return;
        }

        $db = Database::getInstance();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $q = trim($_GET['q'] ?? '');
        $where = '1=1';
        $params = [];

        if ($q !== '') {
            $where .= " AND (
                t.transaction_code LIKE :q_code
                OR u.name LIKE :q_name
                OR t.status LIKE :q_status
            )";

            $like = '%' . $q . '%';
            $params[':q_code'] = $like;
            $params[':q_name'] = $like;
            $params[':q_status'] = $like;
        }

        $allowedSort = [
            'id' => 't.id',
            'transaction_code' => 't.transaction_code',
            'member_name' => 'u.name',
            'borrow_date' => 't.borrow_date',
            'due_date' => 't.due_date',
            'return_date' => 't.return_date',
            'total_books' => 'total_books',
            'status' => 't.status',
            'fine_amount' => 't.fine_amount',
        ];

        $sortBy = $_GET['sort_by'] ?? 'id';
        $sortDir = strtolower($_GET['sort_dir'] ?? 'desc');

        if (!isset($allowedSort[$sortBy])) {
            $sortBy = 'id';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $orderBy = $allowedSort[$sortBy] . ' ' . strtoupper($sortDir);

        $countSql = "
            SELECT COUNT(*) AS total
            FROM tbr_transactions t
            INNER JOIN tbr_users u ON u.id = t.user_id
            WHERE $where
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $dataSql = "
            SELECT
                t.id,
                t.transaction_code,
                t.borrow_date,
                t.due_date,
                t.return_date,
                t.status,
                t.fine_amount,
                u.name AS member_name,
                COALESCE(SUM(td.quantity), 0) AS total_books
            FROM tbr_transactions t
            INNER JOIN tbr_users u ON u.id = t.user_id
            LEFT JOIN tbr_transaction_details td ON td.transaction_id = t.id
            WHERE $where
            GROUP BY
                t.id, t.transaction_code, t.borrow_date, t.due_date, t.return_date, t.status, t.fine_amount, u.name
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ";
        $dataStmt = $db->prepare($dataSql);
        foreach ($params as $k => $v) {
            $dataStmt->bindValue($k, $v);
        }
        $dataStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();

        $transactions = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $transactions,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ]);
    }

    /**
     * Update transaction status from waiting to borrowed.
     */
    public function updateStatus(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $transactionId = (int)($_POST['id'] ?? 0);
        if ($transactionId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid.']);
            return;
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $findStmt = $db->prepare(
                'SELECT id, status FROM tbr_transactions WHERE id = :id LIMIT 1 FOR UPDATE'
            );
            $findStmt->execute([':id' => $transactionId]);
            $transaction = $findStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$transaction) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
                return;
            }

            $currentStatus = strtolower((string)($transaction['status'] ?? ''));
            if (!in_array($currentStatus, ['waiting', 'menunggu'], true)) {
                $db->rollBack();
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Hanya transaksi dengan status Menunggu yang bisa diubah ke Dipinjam.',
                ]);
                return;
            }

            $detailStmt = $db->prepare(
                "SELECT
                    td.book_id,
                    COALESCE(td.quantity, 1) AS quantity,
                    b.title,
                    COALESCE(b.stock, 0) AS stock
                 FROM tbr_transaction_details td
                 INNER JOIN tbr_books b ON b.id = td.book_id
                 WHERE td.transaction_id = :transaction_id
                 FOR UPDATE"
            );
            $detailStmt->execute([':transaction_id' => $transactionId]);
            $details = $detailStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($details)) {
                $db->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Detail transaksi tidak ditemukan.']);
                return;
            }

            foreach ($details as $detail) {
                $quantity = max(1, (int)($detail['quantity'] ?? 1));
                $stock = (int)($detail['stock'] ?? 0);
                $title = (string)($detail['title'] ?? '-');

                if ($stock < $quantity) {
                    $db->rollBack();
                    http_response_code(422);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Stok buku "' . $title . '" tidak mencukupi untuk approval transaksi ini.',
                    ]);
                    return;
                }
            }

            $decrementStmt = $db->prepare(
                'UPDATE tbr_books SET stock = stock - :quantity, updated_at = NOW() WHERE id = :book_id'
            );

            foreach ($details as $detail) {
                $quantity = max(1, (int)($detail['quantity'] ?? 1));
                $bookId = (int)($detail['book_id'] ?? 0);

                $decrementStmt->execute([
                    ':quantity' => $quantity,
                    ':book_id' => $bookId,
                ]);
            }

            $updateStmt = $db->prepare(
                "UPDATE tbr_transactions
                 SET
                    status = 'borrowed',
                    borrow_date = COALESCE(borrow_date, CURDATE()),
                    due_date = COALESCE(due_date, DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
                    updated_at = NOW()
                 WHERE id = :id"
            );
            $updateStmt->execute([':id' => $transactionId]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah status transaksi.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Status transaksi berhasil diubah menjadi Dipinjam.',
        ]);
    }

    /**
     * Approve member return request and restore book stock.
     */
    public function approveReturn(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $transactionId = (int)($_POST['id'] ?? 0);
        if ($transactionId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid.']);
            return;
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $findStmt = $db->prepare(
                'SELECT id, status FROM tbr_transactions WHERE id = :id LIMIT 1 FOR UPDATE'
            );
            $findStmt->execute([':id' => $transactionId]);
            $transaction = $findStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$transaction) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
                return;
            }

            $currentStatus = strtolower((string)($transaction['status'] ?? ''));
            if (!in_array($currentStatus, ['return_requested', 'menunggu_pengembalian'], true)) {
                $db->rollBack();
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Hanya transaksi yang mengajukan pengembalian yang bisa di-approve.',
                ]);
                return;
            }

            $detailStmt = $db->prepare(
                "SELECT
                    td.book_id,
                    COALESCE(td.quantity, 1) AS quantity
                 FROM tbr_transaction_details td
                 WHERE td.transaction_id = :transaction_id
                 FOR UPDATE"
            );
            $detailStmt->execute([':transaction_id' => $transactionId]);
            $details = $detailStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($details)) {
                $db->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Detail transaksi tidak ditemukan.']);
                return;
            }

            $incrementStmt = $db->prepare(
                'UPDATE tbr_books SET stock = stock + :quantity, updated_at = NOW() WHERE id = :book_id'
            );

            foreach ($details as $detail) {
                $quantity = max(1, (int)($detail['quantity'] ?? 1));
                $bookId = (int)($detail['book_id'] ?? 0);
                if ($bookId <= 0) {
                    continue;
                }

                $incrementStmt->execute([
                    ':quantity' => $quantity,
                    ':book_id' => $bookId,
                ]);
            }

            $updateStmt = $db->prepare(
                "UPDATE tbr_transactions
                 SET
                    status = 'returned',
                    return_date = COALESCE(return_date, CURDATE()),
                    updated_at = NOW()
                 WHERE id = :id"
            );
            $updateStmt->execute([':id' => $transactionId]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal approve pengembalian transaksi.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Pengembalian transaksi berhasil di-approve. Stok buku telah ditambahkan.',
        ]);
    }
}
