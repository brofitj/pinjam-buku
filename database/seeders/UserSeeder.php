<?php

use App\Core\Database;

class UserSeeder
{
    public function run()
    {
        $db = Database::getInstance();

        // Ambil role superadmin
        $superadminRole = $db->query("SELECT id FROM tbr_roles WHERE name = 'superadmin' LIMIT 1")->fetch();

        if (!$superadminRole) {
            echo "Superadmin role not found. Run RoleSeeder first.\n";
            return;
        }

        // Ambil role librarian
        $librarianRole = $db->query("SELECT id FROM tbr_roles WHERE name = 'librarian' LIMIT 1")->fetch();

        if (!$librarianRole) {
            echo "Librarian role not found. Run RoleSeeder first.\n";
            return;
        }

        // Ambil role member
        $memberRole = $db->query("SELECT id FROM tbr_roles WHERE name = 'member' LIMIT 1")->fetch();

        if (!$memberRole) {
            echo "Member role not found. Run RoleSeeder first.\n";
            return;
        }

        $stmt = $db->prepare("
            INSERT INTO tbr_users
                (name, gender, phone, address, join_date, role_id, email, username, password, status, created_at, updated_at)
            VALUES
                (:name, :gender, :phone, :address, :join_date, :role_id, :email, :username, :password, :status, NOW(), NOW())
        ");

        $today = date('Y-m-d');

        $users = [
            [
                'name'      => 'Superadmin',
                'gender'    => 'male',
                'phone'     => '628111111111',
                'address'   => 'Jalan Kenangan Block A Nomor 01 Surakarta',
                'join_date' => $today,
                'role_id'   => $superadminRole['id'],
                'email'     => 'superadmin@mail.com',
                'username'  => 'superadmin',
                'password'  => password_hash('superadmin123', PASSWORD_BCRYPT),
                'status'    => 'active',
            ],
            [
                'name'      => 'Librarian',
                'gender'    => 'female',
                'phone'     => '628222222222',
                'address'   => 'Jalan Kenangan Block B Nomor 02 Surakarta',
                'join_date' => $today,
                'role_id'   => $librarianRole['id'],
                'email'     => 'librarian@mail.com',
                'username'  => 'librarian',
                'password'  => password_hash('librarian123', PASSWORD_BCRYPT),
                'status'    => 'active',
            ],
            [
                'name'      => 'John Doe',
                'gender'    => 'male',
                'phone'     => '628333333333',
                'address'   => 'Jalan Sempit Block C Nomor 03 Surakarta',
                'join_date' => $today,
                'role_id'   => $memberRole['id'],
                'email'     => 'john@mail.com',
                'username'  => 'john',
                'password'  => password_hash('member123', PASSWORD_BCRYPT),
                'status'    => 'active',
            ],
        ];

        foreach ($users as $user) {
            $stmt->execute([
                ':name'      => $user['name'],
                ':gender'    => $user['gender'],
                ':phone'     => $user['phone'],
                ':address'   => $user['address'],
                ':join_date' => $user['join_date'],
                ':role_id'   => $user['role_id'],
                ':email'     => $user['email'],
                ':username'  => $user['username'],
                ':password'  => $user['password'],
                ':status'    => $user['status'],
            ]);
        }

        echo "UserSeeder executed successfully.\n";
    }
}