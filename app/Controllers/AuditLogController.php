<?php

namespace App\Controllers;

use App\Models\AuditLogModel;

class AuditLogController extends BaseController
{
    private const LOGS_PER_PAGE = 25;

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $category = trim((string) $this->request->getGet('category'));
        $model = new AuditLogModel();
        $model->select('audit_logs.*, users.full_name AS user_name, documents.original_filename')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->join('documents', 'documents.id = audit_logs.document_id', 'left');
        if ($search !== '') {
            $model->groupStart()
                ->like('users.full_name', $search)
                ->orLike('audit_logs.event', $search)
                ->orLike('audit_logs.description', $search)
                ->orLike('audit_logs.ip_address', $search)
                ->orLike('documents.original_filename', $search)
                ->groupEnd();
        }
        if ($category === 'document') {
            $model->groupStart()->like('audit_logs.event', 'document', 'after')->orLike('audit_logs.event', 'hosting', 'after')->groupEnd();
        } elseif ($category === 'account') {
            $model->whereIn('audit_logs.event', ['login_success', 'login_failed', 'logout', 'password_changed', 'user_created', 'user_password_reset', 'user_status_changed']);
        } elseif ($category === 'problem') {
            $model->like('audit_logs.event', 'failed', 'before');
        }

        $logs = $model->orderBy('audit_logs.id', 'DESC')->paginate(self::LOGS_PER_PAGE);
        $pager = $model->pager;
        $currentPage = max(1, $pager->getCurrentPage());

        return view('logs/audit', [
            'title' => 'Log Aktivitas',
            'logs' => $logs,
            'pager' => $pager,
            'rowNumberStart' => (($currentPage - 1) * self::LOGS_PER_PAGE) + 1,
            'search' => $search,
            'category' => in_array($category, ['document', 'account', 'problem'], true) ? $category : '',
            'summary' => [
                'total' => (new AuditLogModel())->countAllResults(),
                'today' => (new AuditLogModel())->where('created_at >=', date('Y-m-d 00:00:00'))->countAllResults(),
                'documents' => (new AuditLogModel())->groupStart()->like('event', 'document', 'after')->orLike('event', 'hosting', 'after')->groupEnd()->countAllResults(),
                'problems' => (new AuditLogModel())->like('event', 'failed', 'before')->countAllResults(),
            ],
        ]);
    }
}
