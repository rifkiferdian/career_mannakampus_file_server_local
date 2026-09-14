<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\TransferLogModel;
use Config\RemoteStorage;

class Home extends BaseController
{
    public function index(): string
    {
        $documents = new DocumentModel();
        $stats = [
            'total' => $documents->countAllResults(),
            'pending' => (new DocumentModel())->where('transfer_status', 'pending')->countAllResults(),
            'completed' => (new DocumentModel())->where('transfer_status', 'completed')->countAllResults(),
            'failed' => (new DocumentModel())->where('transfer_status', 'failed')->countAllResults(),
        ];

        return view('dashboard', [
            'diskUsage' => (new \App\Libraries\DiskUsage())->read(is_dir(WRITEPATH . 'documents') ? WRITEPATH . 'documents' : WRITEPATH),
            'title' => 'Dashboard',
            'stats' => $stats,
            'remoteConfigured' => config(RemoteStorage::class)->isConfigured(),
            'recentTransfers' => (new TransferLogModel())
                ->select('transfer_logs.*, documents.original_filename, documents.applicant_name')
                ->join('documents', 'documents.id = transfer_logs.document_id', 'left')
                ->orderBy('transfer_logs.id', 'DESC')->findAll(6),
        ]);
    }
}
