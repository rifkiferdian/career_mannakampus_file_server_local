<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class RemoteStorage extends BaseConfig
{
    public string $baseUrl = '';
    public string $clientId = '';
    public string $secret = '';
    public int $timeout = 30;
    public int $maxFileSize = 5_242_880;
    public int $syncBatchLimit = 100;
    public string $pendingPath = 'api/storage/documents/pending';
    public string $downloadPath = 'api/storage/documents/{id}/download';
    public string $confirmPath = 'api/storage/documents/{id}/confirm';
    public string $deletePath = 'api/storage/documents/{id}/delete';

    public function __construct()
    {
        parent::__construct();

        $this->baseUrl = rtrim((string) env('remoteStorage.baseUrl', ''), '/');
        $this->clientId = trim((string) env('remoteStorage.clientId', ''));
        $this->secret = trim((string) env('remoteStorage.secret', ''));
        $this->timeout = (int) env('remoteStorage.timeout', 30);
        $this->maxFileSize = (int) env('remoteStorage.maxFileSize', 5_242_880);
        $this->syncBatchLimit = min(500, max(1, (int) env('remoteStorage.syncBatchLimit', 100)));
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->clientId !== '' && strlen($this->secret) >= 32;
    }
}
