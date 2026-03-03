<?php

namespace App\Controllers;

use App\Core\Database;

class UserController
{
    private function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user']);
    }

    /**
     * Return list of users (superadmin + librarian) as JSON.
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

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
        $offset  = ($page - 1) * $perPage;

        $q = trim($_GET['q'] ?? '');
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
            FROM tbr_users u
            INNER JOIN tbr_roles r ON r.id = u.role_id
            WHERE $where
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $dataSql = "
            SELECT u.id, u.name, u.avatar, u.gender, u.email, u.phone, u.address, u.status, r.name AS role
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

        $users = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $users,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ]);
    }

    /**
     * Create user (default role: librarian) via AJAX.
     */
    public function store(): void
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

        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxBytes = $this->toBytes((string)ini_get('post_max_size'));
        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            http_response_code(413);
            echo json_encode([
                'success' => false,
                'message' => 'Ukuran total upload melebihi batas server (' . $this->toReadableSize($postMaxBytes) . '). Perkecil file atau minta admin menaikkan post_max_size.',
            ]);
            return;
        }

        $name     = trim($_POST['name'] ?? '');
        $gender   = trim($_POST['gender'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $status   = trim($_POST['status'] ?? '');
        $role     = 'librarian';

        if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Ukuran total upload melebihi batas server (' . $this->toReadableSize($postMaxBytes) . '). Perkecil file atau minta admin menaikkan post_max_size.',
            ]);
            return;
        }

        if ($name === '' || $email === '' || $password === '') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Nama, email, dan password wajib diisi.',
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
            return;
        }

        if (strlen($password) < 6) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']);
            return;
        }

        if (!in_array($gender, ['male', 'female'], true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Gender tidak valid.']);
            return;
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
            return;
        }

        $avatarFileName = null;
        if (isset($_FILES['avatar']) && (int)($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $avatarFileName = $this->processAvatarUpload($_FILES['avatar']);
            if ($avatarFileName === null) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Upload avatar gagal. Pastikan file berupa JPG, PNG, atau WEBP.',
                ]);
                return;
            }
        }

        $db = Database::getInstance();

        $roleStmt = $db->prepare("SELECT id FROM tbr_roles WHERE name = :name LIMIT 1");
        $roleStmt->execute([':name' => $role]);
        $roleData = $roleStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$roleData) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Role tidak ditemukan.']);
            return;
        }

        $emailCheckStmt = $db->prepare('SELECT id FROM tbr_users WHERE email = :email LIMIT 1');
        $emailCheckStmt->execute([':email' => $email]);
        if ($emailCheckStmt->fetch(\PDO::FETCH_ASSOC)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar.']);
            return;
        }

        $username = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '', strstr($email, '@', true) ?: $name)));
        if ($username === '') {
            $username = 'user' . time();
        }

        $usernameCheckStmt = $db->prepare('SELECT id FROM tbr_users WHERE username = :username LIMIT 1');
        $usernameCheckStmt->execute([':username' => $username]);
        if ($usernameCheckStmt->fetch(\PDO::FETCH_ASSOC)) {
            $username .= rand(100, 999);
        }

        $insertStmt = $db->prepare(
            "INSERT INTO tbr_users
                (name, avatar, gender, phone, address, join_date, role_id, email, username, password, status, created_at, updated_at)
             VALUES
                (:name, :avatar, :gender, :phone, :address, :join_date, :role_id, :email, :username, :password, :status, NOW(), NOW())"
        );

        $insertStmt->execute([
            ':name'      => $name,
            ':avatar'    => $avatarFileName,
            ':gender'    => $gender,
            ':phone'     => $phone !== '' ? $phone : null,
            ':address'   => $address !== '' ? $address : null,
            ':join_date' => date('Y-m-d'),
            ':role_id'   => (int)$roleData['id'],
            ':email'     => $email,
            ':username'  => $username,
            ':password'  => password_hash($password, PASSWORD_BCRYPT),
            ':status'    => $status,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Data pengelola berhasil ditambahkan.',
        ]);
    }

    /**
     * Get user detail (superadmin/librarian) by id.
     */
    public function show(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return;
        }

        $userId = (int)($_GET['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID pengelola tidak valid.']);
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.id, u.name, u.avatar, u.gender, u.phone, u.address, u.email, u.status, r.name AS role
             FROM tbr_users u
             INNER JOIN tbr_roles r ON r.id = u.role_id
             WHERE u.id = :id AND r.name IN ('superadmin', 'librarian')
             LIMIT 1"
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data pengelola tidak ditemukan.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update user (superadmin/librarian) by id.
     */
    public function update(): void
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

        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxBytes = $this->toBytes((string)ini_get('post_max_size'));
        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            http_response_code(413);
            echo json_encode([
                'success' => false,
                'message' => 'Ukuran total upload melebihi batas server (' . $this->toReadableSize($postMaxBytes) . '). Perkecil file atau minta admin menaikkan post_max_size.',
            ]);
            return;
        }

        $userId   = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $gender   = trim($_POST['gender'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $status   = trim($_POST['status'] ?? '');
        $removeAvatar = in_array((string)($_POST['avatar_remove'] ?? ''), ['1', 'true', 'on'], true);

        if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Ukuran total upload melebihi batas server (' . $this->toReadableSize($postMaxBytes) . '). Perkecil file atau minta admin menaikkan post_max_size.',
            ]);
            return;
        }

        if ($userId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID pengelola tidak valid.']);
            return;
        }

        if ($name === '' || $email === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Nama dan email wajib diisi.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
            return;
        }

        if ($password !== '' && strlen($password) < 6) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']);
            return;
        }

        if (!in_array($gender, ['male', 'female'], true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Gender tidak valid.']);
            return;
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.id, u.avatar, r.name AS role
             FROM tbr_users u
             INNER JOIN tbr_roles r ON r.id = u.role_id
             WHERE u.id = :id AND r.name IN ('superadmin', 'librarian')
             LIMIT 1"
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data pengelola tidak ditemukan.']);
            return;
        }

        $emailCheckStmt = $db->prepare('SELECT id FROM tbr_users WHERE email = :email AND id != :id LIMIT 1');
        $emailCheckStmt->execute([
            ':email' => $email,
            ':id' => $userId,
        ]);
        if ($emailCheckStmt->fetch(\PDO::FETCH_ASSOC)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Email sudah digunakan oleh user lain.']);
            return;
        }

        $oldAvatar = trim((string)($user['avatar'] ?? ''));
        $newAvatar = $oldAvatar !== '' ? $oldAvatar : null;
        $hasUploadedAvatar = isset($_FILES['avatar']) && (int)($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($hasUploadedAvatar) {
            $processedAvatar = $this->processAvatarUpload($_FILES['avatar']);
            if ($processedAvatar === null) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Upload avatar gagal. Pastikan file berupa JPG, PNG, atau WEBP.',
                ]);
                return;
            }
            $newAvatar = $processedAvatar;
        } elseif ($removeAvatar) {
            $newAvatar = null;
        }

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
            ':name' => $name,
            ':avatar' => $newAvatar,
            ':gender' => $gender,
            ':phone' => $phone !== '' ? $phone : null,
            ':address' => $address !== '' ? $address : null,
            ':email' => $email,
            ':status' => $status,
            ':id' => $userId,
        ];

        if ($password !== '') {
            $sql .= ", password = :password";
            $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id = :id LIMIT 1";
        $updateStmt = $db->prepare($sql);
        $updateStmt->execute($params);

        if (($hasUploadedAvatar || $removeAvatar) && $oldAvatar !== '' && $oldAvatar !== $newAvatar) {
            $oldAvatarPathUsers = dirname(__DIR__, 2) . '/storage/avatars/users/' . $oldAvatar;
            $oldAvatarPathMembers = dirname(__DIR__, 2) . '/storage/avatars/members/' . $oldAvatar;
            if (is_file($oldAvatarPathUsers)) {
                @unlink($oldAvatarPathUsers);
            }
            if (is_file($oldAvatarPathMembers)) {
                @unlink($oldAvatarPathMembers);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Data pengelola berhasil diperbarui.',
        ]);
    }

    /**
     * Delete user (superadmin/librarian) by id via AJAX.
     */
    public function delete(): void
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

        $userId = (int)($_POST['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID pengelola tidak valid.']);
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.id, u.avatar, r.name AS role
             FROM tbr_users u
             INNER JOIN tbr_roles r ON r.id = u.role_id
             WHERE u.id = :id AND r.name IN ('superadmin', 'librarian')
             LIMIT 1"
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data pengelola tidak ditemukan.']);
            return;
        }

        if (($user['role'] ?? '') === 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'User dengan role superadmin tidak dapat dihapus.']);
            return;
        }

        $deleteStmt = $db->prepare('DELETE FROM tbr_users WHERE id = :id LIMIT 1');
        $deleteStmt->execute([':id' => $userId]);

        $avatar = trim((string)($user['avatar'] ?? ''));
        if ($avatar !== '' && preg_match('/^[a-zA-Z0-9._-]+$/', $avatar) === 1) {
            $avatarPathUsers = dirname(__DIR__, 2) . '/storage/avatars/users/' . $avatar;
            $avatarPathMembers = dirname(__DIR__, 2) . '/storage/avatars/members/' . $avatar;
            if (is_file($avatarPathUsers)) {
                @unlink($avatarPathUsers);
            }
            if (is_file($avatarPathMembers)) {
                @unlink($avatarPathMembers);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Data pengelola berhasil dihapus.']);
    }

    /**
     * Serve user avatar from storage.
     */
    public function avatar(): void
    {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            return;
        }

        $file = basename((string)($_GET['file'] ?? ''));
        if ($file === '' || preg_match('/^[a-zA-Z0-9._-]+$/', $file) !== 1) {
            http_response_code(404);
            return;
        }

        $pathUsers = dirname(__DIR__, 2) . '/storage/avatars/users/' . $file;
        $pathMembers = dirname(__DIR__, 2) . '/storage/avatars/members/' . $file;

        $path = is_file($pathUsers) ? $pathUsers : (is_file($pathMembers) ? $pathMembers : null);
        if ($path === null) {
            http_response_code(404);
            return;
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (float)$value;
        $unit = strtolower(substr($value, -1));

        if ($unit === 'g') {
            return (int)($number * 1024 * 1024 * 1024);
        }
        if ($unit === 'm') {
            return (int)($number * 1024 * 1024);
        }
        if ($unit === 'k') {
            return (int)($number * 1024);
        }

        return (int)$number;
    }

    private function toReadableSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    private function processAvatarUpload(array $file): ?string
    {
        if (
            !function_exists('imagecreatetruecolor') ||
            !function_exists('imagecopyresampled') ||
            !function_exists('imagejpeg')
        ) {
            return null;
        }

        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            return null;
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return null;
        }

        $info = @getimagesize($tmpPath);
        if (!$info || empty($info[2])) {
            return null;
        }

        $imageType = (int)$info[2];
        $source = null;
        if ($imageType === IMAGETYPE_JPEG) {
            $source = @imagecreatefromjpeg($tmpPath);
        } elseif ($imageType === IMAGETYPE_PNG) {
            $source = @imagecreatefrompng($tmpPath);
        } elseif ($imageType === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            $source = @imagecreatefromwebp($tmpPath);
        }

        if (!$source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $srcX = (int)(($width - $side) / 2);
        $srcY = (int)(($height - $side) / 2);

        $targetSize = 512;
        $target = imagecreatetruecolor($targetSize, $targetSize);
        if (!$target) {
            unset($source);
            return null;
        }

        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        imagecopyresampled($target, $source, 0, 0, $srcX, $srcY, $targetSize, $targetSize, $side, $side);

        $dir = dirname(__DIR__, 2) . '/storage/avatars/users';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            unset($source, $target);
            return null;
        }

        try {
            $rand = bin2hex(random_bytes(4));
        } catch (\Throwable $e) {
            $rand = (string)mt_rand(1000, 9999);
        }

        $fileName = 'user_' . date('YmdHis') . '_' . $rand . '.jpg';
        $savePath = $dir . '/' . $fileName;
        $saved = imagejpeg($target, $savePath, 80);

        unset($source, $target);

        if (!$saved) {
            return null;
        }

        return $fileName;
    }
}
