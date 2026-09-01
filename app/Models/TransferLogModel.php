<?php

namespace App\Models;

use CodeIgniter\Model;

class TransferLogModel extends Model
{
    protected $table = 'transfer_logs';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'document_id', 'user_id', 'action', 'status', 'http_status', 'bytes_received',
        'checksum_valid', 'error_message', 'started_at', 'finished_at',
    ];
}
