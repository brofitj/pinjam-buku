<?php

namespace App\Controllers;

use App\Core\Database;

class HeaderController
{
    /**
     * Build admin header data for current authenticated user.
     *
     * @param array $sessionUser
     * @return array
     */
    public static function getAdminHeaderData(array $sessionUser = []): array
    {
        $authUserId = (int)($sessionUser['id'] ?? 0);
        $headerUser = null;

        if ($authUserId > 0) {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare(
                    "SELECT u.name, u.email, u.avatar, r.name AS role_name
                     FROM tbr_users u
                     INNER JOIN tbr_roles r ON r.id = u.role_id
                     WHERE u.id = :id
                     LIMIT 1"
                );
                $stmt->execute([':id' => $authUserId]);
                $headerUser = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable $e) {
                $headerUser = null;
            }
        }

        $displayName = $headerUser['name'] ?? ($sessionUser['name'] ?? 'User');
        $displayEmail = $headerUser['email'] ?? '-';
        $roleName = $headerUser['role_name'] ?? '-';
        $avatarFile = trim((string)($headerUser['avatar'] ?? ''));

        $avatarUrl = '/themes/metronic/dist/assets/media/avatars/blank.png';
        if ($avatarFile !== '' && preg_match('/^[a-zA-Z0-9._-]+$/', $avatarFile) === 1) {
            $avatarUrl = '/user/avatar?file=' . rawurlencode($avatarFile);
        }

        $roleLabel = ucfirst($roleName);
        $roleBadgeClass = 'kt-badge-secondary';
        if ($roleName === 'superadmin') {
            $roleLabel = 'Superadmin';
            $roleBadgeClass = 'kt-badge-primary';
        } elseif ($roleName === 'librarian') {
            $roleLabel = 'Librarian';
            $roleBadgeClass = 'kt-badge-mono';
        }

        return [
            'name' => $displayName,
            'email' => $displayEmail,
            'avatar_url' => $avatarUrl,
            'role_label' => $roleLabel,
            'role_badge_class' => $roleBadgeClass,
        ];
    }
}
