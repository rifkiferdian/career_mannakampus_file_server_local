<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginAttemptModel extends Model
{
    protected $table = 'login_attempts';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['username', 'ip_address', 'user_agent', 'is_success', 'attempted_at'];
}
