<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->db->table('users')->countAllResults() > 0) {
            return;
        }

        $username = trim((string) env('initialAdmin.username', 'admin'));
        $password = (string) env('initialAdmin.password', '');
        if (strlen($password) < 12) {
            throw new RuntimeException('initialAdmin.password minimal 12 karakter.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('users')->insert([
            'username' => $username,
            'full_name' => (string) env('initialAdmin.fullName', 'Administrator Lokal'),
            'email' => strtolower((string) env('initialAdmin.email', 'admin@local.test')),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_active' => 1,
            'must_change_password' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
