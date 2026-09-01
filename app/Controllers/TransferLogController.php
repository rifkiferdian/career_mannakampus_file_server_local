<?php

namespace App\Controllers;

use App\Models\TransferLogModel;

class TransferLogController extends BaseController
{
    private const LOGS_PER_PAGE = 25;

    public function index(): string
    {
        $model = new TransferLogModel();
        $logs = $model->select('transfer_logs.*, documents.original_filename, documents.applicant_name, users.full_name AS user_name')
            ->join('documents', 'documents.id = transfer_logs.document_id', 'left')
            ->join('users', 'users.id = transfer_logs.user_id', 'left')
            ->orderBy('transfer_logs.id', 'DESC')
            ->paginate(self::LOGS_PER_PAGE);
        $pager = $model->pager;
        $currentPage = max(1, $pager->getCurrentPage());

        return view('logs/transfers', [
            'title' => 'Riwayat Transfer',
            'logs' => $logs,
            'pager' => $pager,
            'rowNumberStart' => (($currentPage - 1) * self::LOGS_PER_PAGE) + 1,
        ]);
    }
}
