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
}
