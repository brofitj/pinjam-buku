<?php

namespace App\Controllers;

use App\Core\Database;

class DashboardController
{
    private function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user']);
    }

    /**
     * Return dashboard counters as JSON.
     */
    public function stats(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return;
        }

        $db = Database::getInstance();

        try {
            $totalBooks = (int)$db->query('SELECT COUNT(*) FROM tbr_books')->fetchColumn();

            $totalMembersStmt = $db->query(
                "SELECT COUNT(*)
                 FROM tbr_users u
                 INNER JOIN tbr_roles r ON r.id = u.role_id
                 WHERE r.name = 'member'"
            );
            $totalMembers = (int)$totalMembersStmt->fetchColumn();

            $totalManagersStmt = $db->query(
                "SELECT COUNT(*)
                 FROM tbr_users u
                 INNER JOIN tbr_roles r ON r.id = u.role_id
                 WHERE r.name IN ('superadmin', 'librarian')"
            );
            $totalManagers = (int)$totalManagersStmt->fetchColumn();

            $activeTransactionsStmt = $db->query(
                "SELECT COUNT(*)
                 FROM tbr_transactions
                 WHERE return_date IS NULL"
            );
            $activeTransactions = (int)$activeTransactionsStmt->fetchColumn();
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memuat statistik dashboard.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'total_books' => $totalBooks,
                'total_members' => $totalMembers,
                'total_managers' => $totalManagers,
                'active_transactions' => $activeTransactions,
            ],
        ]);
    }
}
