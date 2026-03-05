<?php

namespace App\Controllers\Member;

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

    private function getAuthenticatedMemberId(): ?int
    {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return null;
        }

        $sessionUser = $_SESSION['user'] ?? [];
        $userId = (int)($sessionUser['id'] ?? 0);
        $roleId = (int)($sessionUser['role_id'] ?? 0);

        if ($userId <= 0 || $roleId !== 3) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            return null;
        }

        return $userId;
    }

    /**
     * Return transactions owned by logged-in member as JSON.
     */
    public function index(): void
    {
        header('Content-Type: application/json');

        $userId = $this->getAuthenticatedMemberId();
        if ($userId === null) {
            return;
        }

        $db = Database::getInstance();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $q = trim($_GET['q'] ?? '');
        $where = 't.user_id = :user_id';
        $params = [':user_id' => $userId];

        if ($q !== '') {
            $where .= " AND (
                t.transaction_code LIKE :q_code
                OR t.status LIKE :q_status
            )";
            $like = '%' . $q . '%';
            $params[':q_code'] = $like;
            $params[':q_status'] = $like;
        }

        $allowedSort = [
            'id' => 't.id',
            'transaction_code' => 't.transaction_code',
            'borrow_date' => 't.borrow_date',
            'due_date' => 't.due_date',
            'return_date' => 't.return_date',
            'status' => 't.status',
            'fine_amount' => 't.fine_amount',
            'total_books' => 'total_books',
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
                COALESCE(SUM(td.quantity), 0) AS total_books
            FROM tbr_transactions t
            LEFT JOIN tbr_transaction_details td ON td.transaction_id = t.id
            WHERE $where
            GROUP BY
                t.id, t.transaction_code, t.borrow_date, t.due_date, t.return_date, t.status, t.fine_amount
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

        echo json_encode([
            'success' => true,
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
     * Return list of available books for member borrowing flow.
     */
    public function books(): void
    {
        header('Content-Type: application/json');

        if ($this->getAuthenticatedMemberId() === null) {
            return;
        }

        $db = Database::getInstance();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $q = trim($_GET['q'] ?? '');
        $where = 'b.stock > 0';
        $params = [];

        if ($q !== '') {
            $where .= " AND (
                b.book_code LIKE :q_code
                OR b.title LIKE :q_title
                OR b.author LIKE :q_author
            )";
            $like = '%' . $q . '%';
            $params[':q_code'] = $like;
            $params[':q_title'] = $like;
            $params[':q_author'] = $like;
        }

        $allowedSort = [
            'id' => 'b.id',
            'book_code' => 'b.book_code',
            'title' => 'b.title',
            'author' => 'b.author',
            'stock' => 'b.stock',
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

        $countStmt = $db->prepare("SELECT COUNT(*) FROM tbr_books b WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $dataSql = "
            SELECT
                b.id,
                b.book_code,
                b.title,
                b.author,
                b.stock,
                b.cover_image
            FROM tbr_books b
            WHERE $where
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
        $books = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $books,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ]);
    }

    /**
     * Create new transaction request by member with initial waiting status.
     */
    public function store(): void
    {
        header('Content-Type: application/json');

        $userId = $this->getAuthenticatedMemberId();
        if ($userId === null) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $itemsRaw = $_POST['items'] ?? '';
        $items = [];

        if (is_string($itemsRaw) && trim($itemsRaw) !== '') {
            $decoded = json_decode($itemsRaw, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        } elseif (is_array($itemsRaw)) {
            $items = $itemsRaw;
        }

        if (empty($items)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Pilih minimal 1 buku untuk dipinjam.']);
            return;
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $bookId = (int)($item['book_id'] ?? 0);
            $quantity = max(1, (int)($item['quantity'] ?? 1));

            if ($bookId <= 0) {
                continue;
            }

            if (!isset($normalized[$bookId])) {
                $normalized[$bookId] = 0;
            }
            $normalized[$bookId] += $quantity;
        }

        if (empty($normalized)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Data buku yang dipilih tidak valid.']);
            return;
        }

        $db = Database::getInstance();

        $bookIds = array_keys($normalized);
        $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
        $bookStmt = $db->prepare(
            "SELECT id, title, stock
             FROM tbr_books
             WHERE id IN ($placeholders)"
        );
        $bookStmt->execute($bookIds);
        $bookRows = $bookStmt->fetchAll(\PDO::FETCH_ASSOC);

        $bookMap = [];
        foreach ($bookRows as $row) {
            $bookMap[(int)$row['id']] = $row;
        }

        foreach ($normalized as $bookId => $quantity) {
            if (!isset($bookMap[$bookId])) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Ada buku yang tidak ditemukan.']);
                return;
            }

            $stock = (int)($bookMap[$bookId]['stock'] ?? 0);
            if ($stock <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Stok buku "' . $bookMap[$bookId]['title'] . '" sudah habis.']);
                return;
            }

            if ($quantity > $stock) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Jumlah buku "' . $bookMap[$bookId]['title'] . '" melebihi stok tersedia.']);
                return;
            }
        }

        try {
            $db->beginTransaction();

            $transactionCode = $this->generateTransactionCode($db);
            $insertTransactionStmt = $db->prepare(
                "INSERT INTO tbr_transactions
                    (user_id, transaction_code, borrow_date, due_date, return_date, status, fine_amount, created_at, updated_at)
                 VALUES
                    (:user_id, :transaction_code, NULL, NULL, NULL, :status, 0, NOW(), NOW())"
            );
            $insertTransactionStmt->execute([
                ':user_id' => $userId,
                ':transaction_code' => $transactionCode,
                ':status' => 'waiting',
            ]);

            $transactionId = (int)$db->lastInsertId();

            $insertDetailStmt = $db->prepare(
                "INSERT INTO tbr_transaction_details
                    (transaction_id, book_id, quantity, created_at, updated_at)
                 VALUES
                    (:transaction_id, :book_id, :quantity, NOW(), NOW())"
            );

            foreach ($normalized as $bookId => $quantity) {
                $insertDetailStmt->execute([
                    ':transaction_id' => $transactionId,
                    ':book_id' => $bookId,
                    ':quantity' => $quantity,
                ]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal membuat transaksi peminjaman.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat dan menunggu approval admin.',
        ]);
    }

    /**
     * Submit return request for an active borrowing transaction.
     */
    public function requestReturn(): void
    {
        header('Content-Type: application/json');

        $userId = $this->getAuthenticatedMemberId();
        if ($userId === null) {
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
                "SELECT id, status
                 FROM tbr_transactions
                 WHERE id = :id AND user_id = :user_id
                 LIMIT 1
                 FOR UPDATE"
            );
            $findStmt->execute([
                ':id' => $transactionId,
                ':user_id' => $userId,
            ]);
            $transaction = $findStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$transaction) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
                return;
            }

            $status = strtolower((string)($transaction['status'] ?? ''));
            if (!in_array($status, ['borrowed', 'dipinjam', 'overdue', 'terlambat'], true)) {
                $db->rollBack();
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Pengajuan pengembalian hanya untuk transaksi yang sedang dipinjam.',
                ]);
                return;
            }

            $updateStmt = $db->prepare(
                "UPDATE tbr_transactions
                 SET status = 'return_requested', updated_at = NOW()
                 WHERE id = :id"
            );
            $updateStmt->execute([':id' => $transactionId]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mengajukan pengembalian buku.',
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Pengajuan pengembalian berhasil dikirim. Menunggu approval admin.',
        ]);
    }

    private function generateTransactionCode(\PDO $db): string
    {
        $prefix = 'TRX' . date('Ymd');
        $nextNumber = 1;

        $lastCodeStmt = $db->prepare(
            "SELECT transaction_code
             FROM tbr_transactions
             WHERE transaction_code LIKE :prefix
             ORDER BY transaction_code DESC
             LIMIT 1"
        );
        $lastCodeStmt->execute([':prefix' => $prefix . '%']);
        $lastCode = (string)($lastCodeStmt->fetchColumn() ?: '');

        if (preg_match('/^' . preg_quote($prefix, '/') . '(\d{4})$/', $lastCode, $matches)) {
            $nextNumber = ((int)$matches[1]) + 1;
        }

        // Safety loop in case of race condition on concurrent requests.
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);

            $checkStmt = $db->prepare('SELECT id FROM tbr_transactions WHERE transaction_code = :code LIMIT 1');
            $checkStmt->execute([':code' => $candidate]);

            if (!$checkStmt->fetch(\PDO::FETCH_ASSOC)) {
                return $candidate;
            }

            $nextNumber++;
        }

        return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
