<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table = 'documents';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'remote_document_id', 'applicant_id', 'batch_id', 'application_number', 'applicant_name',
        'document_type', 'original_filename', 'stored_filename', 'local_path', 'mime_type',
        'file_size', 'sha256_checksum', 'transfer_status', 'confirmation_status',
        'confirmation_error', 'remote_confirmed_at', 'last_error', 'remote_uploaded_at',
        'downloaded_at', 'downloaded_by',
    ];
}
