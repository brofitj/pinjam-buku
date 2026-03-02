<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Logger;

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
        $oldEmail = $_SESSION['old_email'] ?? '';
        unset($_SESSION['login_error'], $_SESSION['old_email']);

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
}