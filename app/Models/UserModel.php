<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'username', 'full_name', 'email', 'password_hash', 'role', 'is_active',
        'must_change_password', 'last_login_at',
    ];
}
