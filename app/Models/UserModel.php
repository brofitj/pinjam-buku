<?php

namespace App\Models;

use App\Core\Database;

class UserModel
{
    /**
     * Fetch paginated admin users (superadmin + librarian).
     *
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    public function getAdminUsers(string $q, int $page, int $perPage, string $sortBy, string $sortDir): array
    {
        $db = Database::getInstance();
        $offset = ($page - 1) * $perPage;

        $where = "r.name IN ('superadmin', 'librarian')";
        $params = [];

        if ($q !== '') {
            $where .= " AND (
                u.name LIKE :q_name
                OR u.email LIKE :q_email
                OR u.phone LIKE :q_phone
                OR u.address LIKE :q_address
                OR r.name LIKE :q_role
            )";

            $like = '%' . $q . '%';
            $params[':q_name'] = $like;
            $params[':q_email'] = $like;
            $params[':q_phone'] = $like;
            $params[':q_address'] = $like;
            $params[':q_role'] = $like;
        }

        $allowedSort = [
            'id' => 'u.id',
            'name' => 'u.name',
            'gender' => 'u.gender',
            'status' => 'u.status',
            'role' => 'r.name',
        ];
        $sortColumn = $allowedSort[$sortBy] ?? 'u.id';
        $direction = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        $orderBy = $sortColumn . ' ' . $direction;

        $countSql = "
            SELECT COUNT(*) AS total
            FROM tbr_users u
            INNER JOIN tbr_roles r ON r.id = u.role_id
            WHERE {$where}
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $dataSql = "
            SELECT u.id, u.name, u.avatar, u.gender, u.email, u.phone, u.address, u.status, r.name AS role
            FROM tbr_users u
            INNER JOIN tbr_roles r ON r.id = u.role_id
            WHERE {$where}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";
        $dataStmt = $db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value);
        }
        $dataStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();

        return [
            'data' => $dataStmt->fetchAll(\PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    public function findRoleIdByName(string $roleName): ?int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM tbr_roles WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $roleName]);
        $role = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$role) {
            return null;
        }

        return (int)$role['id'];
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $db = Database::getInstance();
        if ($excludeId === null) {
            $stmt = $db->prepare('SELECT id FROM tbr_users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
        }

        $stmt = $db->prepare('SELECT id FROM tbr_users WHERE email = :email AND id != :id LIMIT 1');
        $stmt->execute([
            ':email' => $email,
            ':id' => $excludeId,
        ]);

        return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function usernameExists(string $username): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM tbr_users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createUser(array $payload): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO tbr_users
                (name, avatar, gender, phone, address, join_date, role_id, email, username, password, status, created_at, updated_at)
             VALUES
                (:name, :avatar, :gender, :phone, :address, :join_date, :role_id, :email, :username, :password, :status, NOW(), NOW())"
        );

        $stmt->execute([
            ':name' => $payload['name'],
            ':avatar' => $payload['avatar'],
            ':gender' => $payload['gender'],
            ':phone' => $payload['phone'],
            ':address' => $payload['address'],
            ':join_date' => $payload['join_date'],
            ':role_id' => $payload['role_id'],
            ':email' => $payload['email'],
            ':username' => $payload['username'],
            ':password' => $payload['password'],
            ':status' => $payload['status'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAdminUserById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.id, u.name, u.avatar, u.gender, u.phone, u.address, u.email, u.status, r.name AS role
             FROM tbr_users u
             INNER JOIN tbr_roles r ON r.id = u.role_id
             WHERE u.id = :id AND r.name IN ('superadmin', 'librarian')
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function updateUser(int $id, array $payload, ?string $passwordHash = null): void
    {
        $db = Database::getInstance();
        $sql = "UPDATE tbr_users
                SET name = :name,
                    avatar = :avatar,
                    gender = :gender,
                    phone = :phone,
                    address = :address,
                    email = :email,
                    status = :status,
                    updated_at = NOW()";

        $params = [
            ':name' => $payload['name'],
            ':avatar' => $payload['avatar'],
            ':gender' => $payload['gender'],
            ':phone' => $payload['phone'],
            ':address' => $payload['address'],
            ':email' => $payload['email'],
            ':status' => $payload['status'],
            ':id' => $id,
        ];

        if ($passwordHash !== null) {
            $sql .= ', password = :password';
            $params[':password'] = $passwordHash;
        }

        $sql .= ' WHERE id = :id LIMIT 1';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    public function deleteById(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM tbr_users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
    }
}
