<?php

namespace App\Controllers;

use App\Models\UserModel;

class UserController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

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

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
        $q = trim($_GET['q'] ?? '');

        $sortBy = $_GET['sort_by'] ?? 'id';
        $sortDir = strtolower($_GET['sort_dir'] ?? 'desc');
        $result = $this->userModel->getAdminUsers($q, $page, $perPage, $sortBy, $sortDir);
        $users = $result['data'];
        $total = (int)$result['total'];

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

        $roleId = $this->userModel->findRoleIdByName($role);
        if ($roleId === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Role tidak ditemukan.']);
            return;
        }

        if ($this->userModel->emailExists($email)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar.']);
            return;
        }

        $username = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '', strstr($email, '@', true) ?: $name)));
        if ($username === '') {
            $username = 'user' . time();
        }

        if ($this->userModel->usernameExists($username)) {
            $username .= rand(100, 999);
        }

        $this->userModel->createUser([
            'name' => $name,
            'avatar' => $avatarFileName,
            'gender' => $gender,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'join_date' => date('Y-m-d'),
            'role_id' => $roleId,
            'email' => $email,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'status' => $status,
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

        $user = $this->userModel->findAdminUserById($userId);

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

        $user = $this->userModel->findAdminUserById($userId);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data pengelola tidak ditemukan.']);
            return;
        }

        if ($this->userModel->emailExists($email, $userId)) {
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

        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null;
        $this->userModel->updateUser(
            $userId,
            [
                'name' => $name,
                'avatar' => $newAvatar,
                'gender' => $gender,
                'phone' => $phone !== '' ? $phone : null,
                'address' => $address !== '' ? $address : null,
                'email' => $email,
                'status' => $status,
            ],
            $passwordHash
        );

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

        $user = $this->userModel->findAdminUserById($userId);

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

        $this->userModel->deleteById($userId);

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
