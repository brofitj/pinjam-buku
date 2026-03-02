<?php

use App\Core\Database;

class RoleSeeder
{
    public function run()
    {
        $db = Database::getInstance();

        $stmt = $db->prepare("
            INSERT INTO tbr_roles
                (name, description, created_at, updated_at)
            VALUES
                (:name, :description, NOW(), NOW())
        ");

        $roles = [
            [
                ':name'        => 'superadmin',
                ':description' => 'Administrator Sistem',
            ],
            [
                ':name'        => 'librarian',
                ':description' => 'Petugas Perpustakaan',
            ],
            [
                ':name'        => 'member',
                ':description' => 'Anggota Perpustakaan',
            ],
        ];

        foreach ($roles as $role) {
            $stmt->execute($role);
        }

        echo "RoleSeeder executed successfully.\n";
    }
}