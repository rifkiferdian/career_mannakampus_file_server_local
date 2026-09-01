<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class RemoteStorage extends BaseConfig
{
    public string $baseUrl = '';
    public string $apiKey = '';
    public int $timeout = 30;
    public int $maxFileSize = 5_242_880;
    public string $pendingPath = 'api/storage/documents/pending';
    public string $downloadPath = 'api/storage/documents/{id}/download';
    public string $confirmPath = 'api/storage/documents/{id}/confirm';

    public function __construct()
    {
        parent::__construct();

        $this->baseUrl = rtrim((string) env('remoteStorage.baseUrl', ''), '/');
        $this->apiKey = (string) env('remoteStorage.apiKey', '');
        $this->timeout = (int) env('remoteStorage.timeout', 30);
        $this->maxFileSize = (int) env('remoteStorage.maxFileSize', 5_242_880);
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '';
    }
}
