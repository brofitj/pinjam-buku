<?php

namespace App\Controllers;

use App\Core\Database;

class MemberController
{
    /**
     * Ensure authenticated user session exists.
     */
    private function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user']);
    }

    /**
     * Return list of members as JSON.
     *
     * @return void
     * @throws \Exception
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

        // Filter pencarian.
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

        // Validasi sort_by dan sort_dir.
        $allowedSort = [
            'id'     => 'u.id',
            'name'   => 'u.name',
            'gender' => 'u.gender',
            'status' => 'u.status',
        ];

        $sortBy  = $_GET['sort_by'] ?? 'id';
        $sortDir = strtolower($_GET['sort_dir'] ?? 'desc');

        if (!isset($allowedSort[$sortBy])) {
            $sortBy = 'id';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $orderBy = $allowedSort[$sortBy] . ' ' . strtoupper($sortDir);

        // Hitung total data untuk pagination.
        $countSql = "
            SELECT COUNT(*) AS total
            FROM tbr_users u
            INNER JOIN tbr_roles r ON r.id = u.role_id
            WHERE $where
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Ambil data dengan pagination.
        $dataSql = "
            SELECT u.id, u.name, u.avatar, u.gender, u.email, u.phone, u.address, u.status
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

    /**
     * Create member via AJAX.
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

        $name     = trim($_POST['name'] ?? '');
        $gender   = trim($_POST['gender'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $status   = trim($_POST['status'] ?? '');

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
            echo json_encode([
                'success' => false,
                'message' => 'Password minimal 6 karakter.',
            ]);
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

        $memberRoleStmt = $db->prepare("SELECT id FROM tbr_roles WHERE name = 'member' LIMIT 1");
        $memberRoleStmt->execute();
        $memberRole = $memberRoleStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$memberRole) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Role member tidak ditemukan.']);
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
            $username = 'member' . time();
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
            ':role_id'   => (int)$memberRole['id'],
            ':email'     => $email,
            ':username'  => $username,
            ':password'  => password_hash($password, PASSWORD_BCRYPT),
            ':status'    => $status,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Data anggota berhasil ditambahkan.',
        ]);
    }

    /**
     * Serve member avatar from storage.
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

        $path = dirname(__DIR__, 2) . '/storage/avatars/members/' . $file;
        if (!is_file($path)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
    }

    /**
     * Delete member by id via AJAX.
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

        $memberId = (int)($_POST['id'] ?? 0);

        if ($memberId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID anggota tidak valid.']);
            return;
        }

        $db = Database::getInstance();

        $memberStmt = $db->prepare(
            "SELECT u.id, u.avatar
             FROM tbr_users u
             INNER JOIN tbr_roles r ON r.id = u.role_id
             WHERE u.id = :id AND r.name = 'member'
             LIMIT 1"
        );
        $memberStmt->execute([':id' => $memberId]);
        $member = $memberStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$member) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data anggota tidak ditemukan.']);
            return;
        }

        $deleteStmt = $db->prepare('DELETE FROM tbr_users WHERE id = :id LIMIT 1');
        $deleteStmt->execute([':id' => $memberId]);

        $avatar = trim((string)($member['avatar'] ?? ''));
        if ($avatar !== '' && preg_match('/^[a-zA-Z0-9._-]+$/', $avatar) === 1) {
            $avatarPath = dirname(__DIR__, 2) . '/storage/avatars/members/' . $avatar;
            if (is_file($avatarPath)) {
                @unlink($avatarPath);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Data anggota berhasil dihapus.']);
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

        $dir = dirname(__DIR__, 2) . '/storage/avatars/members';
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

        $fileName = 'member_' . date('YmdHis') . '_' . $rand . '.jpg';
        $savePath = $dir . '/' . $fileName;
        $saved = imagejpeg($target, $savePath, 80);

        unset($source, $target);

        if (!$saved) {
            return null;
        }

        return $fileName;
    }
}
