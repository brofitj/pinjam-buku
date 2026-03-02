<?php

namespace App\Controllers;

use App\Core\Database;

class MemberController
{
    /**
     * Return list of members as JSON.
     *
     * @return void
     * @throws \Exception
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Unauthenticated']);
            return;
        }

        $db = Database::getInstance();

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
        $offset  = ($page - 1) * $perPage;

        /**
         * optional: server-side search
         */
        $q = trim($_GET['q'] ?? '');
        $where  = "r.name = 'member'";
        $params = [];

        if ($q !== '') {
            $where .= " AND (
                u.name   LIKE :q_name
                OR u.email LIKE :q_email
                OR u.phone LIKE :q_phone
                OR u.address LIKE :q_address
            )";

            $like = '%' . $q . '%';
            $params[':q_name']    = $like;
            $params[':q_email']   = $like;
            $params[':q_phone']   = $like;
            $params[':q_address'] = $like;
        }

        /**
         * mapping kolom yang boleh di-sort
         */
        $allowedSort = [
            'name'   => 'u.name',
            'gender' => 'u.gender',
            'status' => 'u.status',
        ];

        $sortBy  = $_GET['sort_by'] ?? 'name';
        $sortDir = strtolower($_GET['sort_dir'] ?? 'asc');

        if (!isset($allowedSort[$sortBy])) {
            $sortBy = 'name';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $orderBy = $allowedSort[$sortBy] . ' ' . strtoupper($sortDir);

        /**
         * hitung total
         */
        $countSql = "
            SELECT COUNT(*) AS total
            FROM tbr_users u
            INNER JOIN tbr_roles r ON r.id = u.role_id
            WHERE $where
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        /**
         * ambil data halaman ini
         */
        $dataSql = "
            SELECT u.id, u.name, u.gender, u.email, u.phone, u.address, u.status
            FROM tbr_users u
            INNER JOIN tbr_roles r ON r.id = u.role_id
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

        $members = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $members,
            'meta' => [
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ]);
    }
}