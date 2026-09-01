<?php

namespace App\Controllers;

use App\Models\AuditLogModel;

class AuditLogController extends BaseController
{
    public function index(): string
    {
        $model = new AuditLogModel();

        return view('logs/audit', [
            'title' => 'Log Aktivitas',
            'logs' => $model->select('audit_logs.*, users.full_name AS user_name, documents.original_filename')
                ->join('users', 'users.id = audit_logs.user_id', 'left')
                ->join('documents', 'documents.id = audit_logs.document_id', 'left')
                ->orderBy('audit_logs.id', 'DESC')->paginate(25),
            'pager' => $model->pager,
        ]);
    }
}
