<?php

namespace App\Controllers;

use App\Models\TransferLogModel;

class TransferLogController extends BaseController
{
    private const LOGS_PER_PAGE = 25;

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $action = trim((string) $this->request->getGet('action'));
        $model = new TransferLogModel();
        $model->select('transfer_logs.*, documents.original_filename, documents.applicant_name, users.full_name AS user_name')
            ->join('documents', 'documents.id = transfer_logs.document_id', 'left')
            ->join('users', 'users.id = transfer_logs.user_id', 'left');
        if ($search !== '') {
            $model->groupStart()
                ->like('documents.applicant_name', $search)
                ->orLike('documents.original_filename', $search)
                ->orLike('users.full_name', $search)
                ->orLike('transfer_logs.error_message', $search)
                ->groupEnd();
        }
        if (in_array($status, ['started', 'success', 'failed'], true)) {
            $model->where('transfer_logs.status', $status);
        }
        if (in_array($action, ['download', 'retry', 'confirm', 'delete_hosting'], true)) {
            $model->where('transfer_logs.action', $action);
        }

        $logs = $model
            ->orderBy('transfer_logs.id', 'DESC')
            ->paginate(self::LOGS_PER_PAGE);
        $pager = $model->pager;
        $currentPage = max(1, $pager->getCurrentPage());

        return view('logs/transfers', [
            'title' => 'Riwayat Transfer',
            'logs' => $logs,
            'pager' => $pager,
            'rowNumberStart' => (($currentPage - 1) * self::LOGS_PER_PAGE) + 1,
            'search' => $search,
            'status' => $status,
            'action' => $action,
            'summary' => [
                'total' => (new TransferLogModel())->countAllResults(),
                'success' => (new TransferLogModel())->where('status', 'success')->countAllResults(),
                'failed' => (new TransferLogModel())->where('status', 'failed')->countAllResults(),
                'deleted' => (new TransferLogModel())->where('action', 'delete_hosting')->where('status', 'success')->countAllResults(),
            ],
        ]);
    }
}
