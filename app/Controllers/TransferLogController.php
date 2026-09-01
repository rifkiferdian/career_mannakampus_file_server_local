<?php

namespace App\Controllers;

use App\Models\TransferLogModel;

class TransferLogController extends BaseController
{
    public function index(): string
    {
        $model = new TransferLogModel();

        return view('logs/transfers', [
            'title' => 'Riwayat Transfer',
            'logs' => $model->select('transfer_logs.*, documents.original_filename, documents.applicant_name, users.full_name AS user_name')
                ->join('documents', 'documents.id = transfer_logs.document_id', 'left')
                ->join('users', 'users.id = transfer_logs.user_id', 'left')
                ->orderBy('transfer_logs.id', 'DESC')->paginate(25),
            'pager' => $model->pager,
        ]);
    }
}
