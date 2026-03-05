<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Logger;
use App\Services\MailService;

class AuthController
{
    /**
     * Login
     * @return void
     * @throws \Exception
     */
    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user'])) {
            $this->redirectByRole($_SESSION['user']['role_id']);
        }

        $error = $_SESSION['login_error'] ?? null;
        $success = $_SESSION['login_success'] ?? null;
        $oldEmail = $_SESSION['old_email'] ?? '';
        unset($_SESSION['login_error'], $_SESSION['login_success'], $_SESSION['old_email']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($email === '' || $password === '') {
                $_SESSION['login_error'] = "Email dan password wajib diisi.";
                $_SESSION['old_email'] = $email;
                header("Location: /login");
                exit;
            } else {

                $db = Database::getInstance();

                $stmt = $db->prepare("SELECT * FROM tbr_users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);

                $user = $stmt->fetch();

                if (!$user || !password_verify($password, $user['password'])) {

                    Logger::warning('Login gagal', [
                        'email' => $email,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);

                    $_SESSION['login_error'] = "Email atau password salah.";
                    $_SESSION['old_email'] = $email;
                    header("Location: /login");
                    exit;

                } elseif ((int)$user['role_id'] === 3 && empty($user['email_verified_at'])) {

                    $_SESSION['login_error'] = "Akun anggota belum terverifikasi. Silakan cek email Anda.";
                    $_SESSION['old_email'] = $email;
                    header("Location: /login");
                    exit;

                } else {

                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'role_id' => $user['role_id']
                    ];

                    Logger::info('Login berhasil', [
                        'user_id' => $user['id'],
                        'email' => $user['email']
                    ]);

                    $this->redirectByRole($user['role_id']);
                }
            }
        }

        require __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Member self registration.
     *
     * @return void
     * @throws \Exception
     */
    public function register()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user'])) {
            $this->redirectByRole($_SESSION['user']['role_id']);
        }

        $error = $_SESSION['register_error'] ?? null;
        $success = $_SESSION['register_success'] ?? null;
        $oldName = $_SESSION['register_old_name'] ?? '';
        $oldEmail = $_SESSION['register_old_email'] ?? '';
        unset(
            $_SESSION['register_error'],
            $_SESSION['register_success'],
            $_SESSION['register_old_name'],
            $_SESSION['register_old_email']
        );

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim((string)($_POST['name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');

            $_SESSION['register_old_name'] = $name;
            $_SESSION['register_old_email'] = $email;

            if ($name === '' || $email === '' || $password === '' || $passwordConfirmation === '') {
                $_SESSION['register_error'] = 'Nama, email, password, dan konfirmasi password wajib diisi.';
                header('Location: /register');
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['register_error'] = 'Format email tidak valid.';
                header('Location: /register');
                exit;
            }

            if (strlen($password) < 6) {
                $_SESSION['register_error'] = 'Password minimal 6 karakter.';
                header('Location: /register');
                exit;
            }

            if ($password !== $passwordConfirmation) {
                $_SESSION['register_error'] = 'Konfirmasi password tidak sama.';
                header('Location: /register');
                exit;
            }

            $db = Database::getInstance();

            $memberRoleStmt = $db->prepare("SELECT id FROM tbr_roles WHERE name = 'member' LIMIT 1");
            $memberRoleStmt->execute();
            $memberRole = $memberRoleStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$memberRole) {
                $_SESSION['register_error'] = 'Role member tidak ditemukan. Hubungi admin.';
                header('Location: /register');
                exit;
            }

            $userStmt = $db->prepare('SELECT id, name, role_id, email_verified_at FROM tbr_users WHERE email = :email LIMIT 1');
            $userStmt->execute([':email' => $email]);
            $existingUser = $userStmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            $memberRoleId = (int)$memberRole['id'];
            $memberId = 0;
            $memberName = $name;

            if ($existingUser) {
                if ((int)$existingUser['role_id'] !== $memberRoleId) {
                    $_SESSION['register_error'] = 'Email sudah digunakan oleh akun lain.';
                    header('Location: /register');
                    exit;
                }

                if (!empty($existingUser['email_verified_at'])) {
                    $_SESSION['register_error'] = 'Email sudah terdaftar dan terverifikasi. Silakan login.';
                    header('Location: /register');
                    exit;
                }

                $memberId = (int)$existingUser['id'];
                $memberName = trim((string)($existingUser['name'] ?? '')) !== ''
                    ? (string)$existingUser['name']
                    : $name;
            } else {
                $usernameBase = strtolower(trim((string)(strstr($email, '@', true) ?: $name)));
                $usernameBase = (string)preg_replace('/[^a-z0-9]+/', '', $usernameBase);
                if ($usernameBase === '') {
                    $usernameBase = 'member';
                }

                $username = $usernameBase;
                $attempt = 0;
                while (true) {
                    $checkUsernameStmt = $db->prepare('SELECT id FROM tbr_users WHERE username = :username LIMIT 1');
                    $checkUsernameStmt->execute([':username' => $username]);
                    if (!$checkUsernameStmt->fetch(\PDO::FETCH_ASSOC)) {
                        break;
                    }

                    $attempt++;
                    if ($attempt > 10) {
                        $username = $usernameBase . (string)time();
                        break;
                    }

                    $username = $usernameBase . (string)rand(100, 999);
                }

                $db->beginTransaction();
                try {
                    $insertStmt = $db->prepare(
                        "INSERT INTO tbr_users
                            (name, avatar, gender, phone, address, join_date, role_id, email, email_verified_at, username, password, status, created_at, updated_at)
                         VALUES
                            (:name, NULL, NULL, NULL, NULL, :join_date, :role_id, :email, NULL, :username, :password, 'active', NOW(), NOW())"
                    );

                    $insertStmt->execute([
                        ':name' => $name,
                        ':join_date' => date('Y-m-d'),
                        ':role_id' => $memberRoleId,
                        ':email' => $email,
                        ':username' => $username,
                        ':password' => password_hash($password, PASSWORD_BCRYPT),
                    ]);

                    $memberId = (int)$db->lastInsertId();
                    $memberName = $name;

                    $db->commit();
                } catch (\Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }

                    Logger::error('Member self registration failed.', [
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);

                    $_SESSION['register_error'] = 'Gagal membuat akun. Silakan coba lagi.';
                    header('Location: /register');
                    exit;
                }
            }

            if ($memberId <= 0) {
                $_SESSION['register_error'] = 'Gagal membuat akun. Silakan coba lagi.';
                header('Location: /register');
                exit;
            }

            try {
                $verificationToken = $this->createEmailVerificationToken($db, $memberId);
            } catch (\Throwable $e) {
                Logger::error('Generate verification token failed on self registration.', [
                    'member_id' => $memberId,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                $_SESSION['register_error'] = 'Akun berhasil dibuat, tetapi token verifikasi gagal dibuat. Hubungi admin.';
                header('Location: /register');
                exit;
            }

            $mailSent = $this->sendMemberVerificationEmail($email, $memberName, $verificationToken);

            if (!$mailSent) {
                $_SESSION['register_success'] = 'Akun berhasil dibuat, tetapi email verifikasi gagal dikirim. Hubungi admin untuk bantuan.';
                header('Location: /register');
                exit;
            }

            unset($_SESSION['register_old_name'], $_SESSION['register_old_email']);
            $_SESSION['login_success'] = 'Pendaftaran berhasil. Silakan cek email Anda untuk verifikasi akun sebelum login.';
            $_SESSION['old_email'] = $email;

            header('Location: /login');
            exit;
        }

        require __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * Redirect to dashboard by role
     * Superadmin, Librarian, Member
     * @param int $roleId
     * @return void
     * @throws \Exception
     */
    private function redirectByRole($roleId)
    {
        switch ($roleId) {
            case 1:
                header("Location: /dashboard");
                break;

            case 2:
                header("Location: /librarian/dashboard");
                break;

            case 3:
            default:
                header("Location: /member/dashboard");
                break;
        }

        exit;
    }

    /**
     * Logout
     * @return void
     * @throws \Exception
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        header("Location: /login");
        exit;
    }

    private function createEmailVerificationToken(\PDO $db, int $userId): string
    {
        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            $token = hash('sha256', uniqid((string)$userId, true) . (string)mt_rand(1000, 9999));
        }

        $tokenHash = hash('sha256', $token);

        $cleanupStmt = $db->prepare('DELETE FROM tbr_email_verifications WHERE user_id = :user_id');
        $cleanupStmt->execute([':user_id' => $userId]);

        $insertStmt = $db->prepare(
            "INSERT INTO tbr_email_verifications
                (user_id, token_hash, expires_at, verified_at, created_at, updated_at)
             VALUES
                (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 24 HOUR), NULL, NOW(), NOW())"
        );
        $insertStmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
        ]);

        return $token;
    }

    private function sendMemberVerificationEmail(string $email, string $name, string $token): bool
    {
        $mailConfig = require dirname(__DIR__, 2) . '/config/mail.php';

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $verifyUrl = $scheme . '://' . $host . '/member/verify-email?token=' . rawurlencode($token);

        $subject = 'Verifikasi Akun Anggota';
        $message = "Halo {$name},\n\nSilakan verifikasi akun Anda dengan membuka tautan berikut:\n{$verifyUrl}\n\nTautan berlaku 24 jam.\n";
        $mailer = new MailService($mailConfig);
        $sent = $mailer->send($email, $name, $subject, $message);

        if (!$sent) {
            Logger::warning('Self registration verification email failed.', [
                'email' => $email,
                'verify_url' => $verifyUrl,
            ]);
            return false;
        }

        Logger::info('Self registration verification email sent.', [
            'email' => $email,
        ]);

        return true;
    }
}
