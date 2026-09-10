<?php

namespace App\Services;

use App\Libraries\StorageSyncSignature;
use App\Models\DocumentModel;
use App\Models\TransferLogModel;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use Config\RemoteStorage;
use RuntimeException;
use Throwable;

class RemoteDocumentService
{
    private RemoteStorage $config;
    private CURLRequest $client;

    public function __construct()
    {
        $this->config = config(RemoteStorage::class);
        $this->client = service('curlrequest', [
            'timeout' => $this->config->timeout,
            'connect_timeout' => 10,
            'http_errors' => false,
        ], null, null, false);
    }

    /** @return array{metadata: int, downloaded: int, confirmed: int, failed: int} */
    public function syncAll(int $userId): array
    {
        $result = ['metadata' => $this->syncMetadata(), 'downloaded' => 0, 'confirmed' => 0, 'failed' => 0];

        $unconfirmed = (new DocumentModel())
            ->where('transfer_status', 'completed')
            ->whereIn('confirmation_status', ['pending', 'failed'])
            ->orderBy('id', 'ASC')
            ->findAll($this->config->syncBatchLimit);
        foreach ($unconfirmed as $document) {
            if ($this->confirmStored((int) $document['id'], $userId)) {
                $result['confirmed']++;
            } else {
                $result['failed']++;
            }
        }

        $downloadable = (new DocumentModel())
            ->whereIn('transfer_status', ['pending', 'failed'])
            ->orderBy('id', 'ASC')
            ->findAll($this->config->syncBatchLimit);
        foreach ($downloadable as $document) {
            try {
                $download = $this->download((int) $document['id'], $userId);
                $result['downloaded']++;
                if ($download['confirmed']) {
                    $result['confirmed']++;
                } else {
                    $result['failed']++;
                }
            } catch (Throwable) {
                $result['failed']++;
            }
        }

        return $result;
    }

    public function syncMetadata(): int
    {
        $this->assertConfigured();
        $response = $this->request('GET', $this->config->pendingPath);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException($this->remoteError($response, 'mengambil daftar dokumen'));
        }

        $payload = json_decode((string) $response->getBody(), true);
        $rows = is_array($payload) ? ($payload['documents'] ?? $payload['data'] ?? null) : null;
        if (! is_array($rows)) {
            throw new RuntimeException('Format daftar dokumen dari hosting tidak valid.');
        }

        $model = new DocumentModel();
        $count = 0;
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id']) || (int) $row['id'] < 1) {
                continue;
            }
            $remoteId = (int) $row['id'];
            $existing = $model->where('remote_document_id', $remoteId)->first();
            $data = [
                'remote_document_id' => $remoteId,
                'applicant_id' => $this->nullableInt($row['applicant_id'] ?? null),
                'batch_id' => $this->nullableInt($row['batch_id'] ?? null),
                'application_number' => $this->limited($row['application_number'] ?? $row['batch_number'] ?? null, 50),
                'applicant_name' => $this->limited($row['applicant_name'] ?? 'Pelamar', 150) ?: 'Pelamar',
                'document_type' => $this->limited($row['document_type'] ?? 'application_bundle', 50) ?: 'application_bundle',
                'original_filename' => $this->limited($row['original_filename'] ?? ('dokumen-' . $remoteId . '.pdf'), 255),
                'mime_type' => $this->limited($row['mime_type'] ?? 'application/pdf', 100) ?: 'application/pdf',
                'file_size' => $this->nullableInt($row['file_size'] ?? null),
                'sha256_checksum' => $this->checksum($row['sha256_checksum'] ?? null),
                'remote_uploaded_at' => $this->dateTime($row['uploaded_at'] ?? null),
            ];
            if ($existing === null) {
                $data['transfer_status'] = 'pending';
                $data['confirmation_status'] = 'pending';
                $model->insert($data);
            } else {
                if ($existing['transfer_status'] === 'completed') {
                    unset($data['mime_type'], $data['file_size'], $data['sha256_checksum']);
                }
                $model->update((int) $existing['id'], $data);
            }
            $count++;
        }

        return $count;
    }

    /** @return array{confirmed: bool, bytes: int, checksum: string} */
    public function download(int $documentId, int $userId): array
    {
        $this->assertConfigured();
        $documents = new DocumentModel();
        $document = $documents->find($documentId);
        if ($document === null) {
            throw new RuntimeException('Dokumen tidak ditemukan.');
        }
        if ($document['transfer_status'] === 'completed') {
            return [
                'confirmed' => $this->confirmStored($documentId, $userId),
                'bytes' => (int) $document['file_size'],
                'checksum' => (string) $document['sha256_checksum'],
            ];
        }

        $logModel = new TransferLogModel();
        $logId = (int) $logModel->insert([
            'document_id' => $documentId,
            'user_id' => $userId,
            'action' => $document['transfer_status'] === 'failed' ? 'retry' : 'download',
            'status' => 'started',
            'started_at' => date('Y-m-d H:i:s'),
        ], true);
        $documents->update($documentId, ['transfer_status' => 'downloading', 'last_error' => null]);
        $finalPath = null;

        try {
            $remotePath = str_replace('{id}', (string) (int) $document['remote_document_id'], $this->config->downloadPath);
            $response = $this->request('GET', $remotePath, '', 'application/pdf');
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new RuntimeException($this->remoteError($response, 'mengunduh file'));
            }

            $body = (string) $response->getBody();
            $bytes = strlen($body);
            if ($bytes < 8 || $bytes > $this->config->maxFileSize) {
                throw new RuntimeException('Ukuran file tidak valid atau melebihi batas penyimpanan lokal.');
            }
            if (! str_starts_with($body, '%PDF-')) {
                throw new RuntimeException('Isi file dari hosting bukan dokumen PDF yang valid.');
            }

            $checksum = hash('sha256', $body);
            $expected = $this->checksum($document['sha256_checksum'] ?? null)
                ?? $this->checksum($response->getHeaderLine('X-Checksum-SHA256'));
            if ($expected === null || ! hash_equals($expected, $checksum)) {
                throw new RuntimeException('Checksum file tidak tersedia atau tidak cocok. File tidak disimpan.');
            }

            $relativeDirectory = date('Y/m');
            $absoluteDirectory = WRITEPATH . 'documents' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
            if (! is_dir($absoluteDirectory) && ! mkdir($absoluteDirectory, 0750, true) && ! is_dir($absoluteDirectory)) {
                throw new RuntimeException('Folder penyimpanan lokal tidak dapat dibuat.');
            }
            $storedFilename = bin2hex(random_bytes(16)) . '.pdf';
            $relativePath = $relativeDirectory . '/' . $storedFilename;
            $finalPath = $absoluteDirectory . DIRECTORY_SEPARATOR . $storedFilename;
            $temporaryPath = $finalPath . '.part';
            if (file_put_contents($temporaryPath, $body, LOCK_EX) !== $bytes || ! rename($temporaryPath, $finalPath)) {
                @unlink($temporaryPath);
                throw new RuntimeException('File gagal ditulis ke penyimpanan lokal.');
            }

            $documents->update($documentId, [
                'stored_filename' => $storedFilename,
                'local_path' => $relativePath,
                'mime_type' => 'application/pdf',
                'file_size' => $bytes,
                'sha256_checksum' => $checksum,
                'transfer_status' => 'completed',
                'confirmation_status' => 'pending',
                'confirmation_error' => null,
                'last_error' => null,
                'downloaded_at' => date('Y-m-d H:i:s'),
                'downloaded_by' => $userId,
            ]);
            $logModel->update($logId, [
                'status' => 'success',
                'http_status' => $statusCode,
                'bytes_received' => $bytes,
                'checksum_valid' => 1,
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $exception) {
            if ($finalPath !== null && is_file($finalPath)) {
                @unlink($finalPath);
            }
            $documents->update($documentId, [
                'transfer_status' => 'failed',
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            $logModel->update($logId, [
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            throw $exception;
        }

        $confirmed = $this->confirmStored($documentId, $userId);

        return ['confirmed' => $confirmed, 'bytes' => $bytes, 'checksum' => $checksum];
    }

    public function confirmStored(int $documentId, int $userId): bool
    {
        $this->assertConfigured();
        $documents = new DocumentModel();
        $document = $documents->find($documentId);
        if ($document === null || $document['transfer_status'] !== 'completed') {
            return false;
        }

        $logModel = new TransferLogModel();
        $logId = (int) $logModel->insert([
            'document_id' => $documentId,
            'user_id' => $userId,
            'action' => 'confirm',
            'status' => 'started',
            'started_at' => date('Y-m-d H:i:s'),
        ], true);

        try {
            $localPath = $this->resolveLocalPath((string) $document['local_path']);
            if ($localPath === null) {
                throw new RuntimeException('File lokal tidak ditemukan saat akan dikonfirmasi.');
            }
            $bytes = filesize($localPath);
            $checksum = hash_file('sha256', $localPath);
            if ($bytes === false || ! is_string($checksum)
                || $bytes !== (int) $document['file_size']
                || ! hash_equals((string) $document['sha256_checksum'], $checksum)) {
                throw new RuntimeException('Integritas file lokal berubah; konfirmasi dibatalkan.');
            }

            $body = json_encode([
                'sha256_checksum' => $checksum,
                'file_size' => $bytes,
                'downloaded_at' => date(DATE_ATOM),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $remotePath = str_replace('{id}', (string) (int) $document['remote_document_id'], $this->config->confirmPath);
            $response = $this->request('POST', $remotePath, $body);
            if (! in_array($response->getStatusCode(), [200, 204], true)) {
                throw new RuntimeException($this->remoteError($response, 'mengonfirmasi file'));
            }

            $documents->update($documentId, [
                'confirmation_status' => 'confirmed',
                'confirmation_error' => null,
                'remote_confirmed_at' => date('Y-m-d H:i:s'),
            ]);
            $logModel->update($logId, [
                'status' => 'success',
                'http_status' => $response->getStatusCode(),
                'bytes_received' => $bytes,
                'checksum_valid' => 1,
                'finished_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (Throwable $exception) {
            $documents->update($documentId, [
                'confirmation_status' => 'failed',
                'confirmation_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            $logModel->update($logId, [
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);

            return false;
        }
    }

    /** @return array{deletedAt: string, alreadyDeleted: bool} */
    public function deleteFromHosting(int $documentId, int $userId): array
    {
        $this->assertConfigured();
        $documents = new DocumentModel();
        $document = $documents->find($documentId);
        if ($document === null) {
            throw new RuntimeException('Dokumen tidak ditemukan.');
        }
        if ($document['transfer_status'] !== 'completed' || $document['confirmation_status'] !== 'confirmed') {
            throw new RuntimeException('File hosting hanya dapat dihapus setelah dokumen lokal selesai dan terkonfirmasi.');
        }
        if (! empty($document['hosting_deleted_at'])) {
            return ['deletedAt' => (string) $document['hosting_deleted_at'], 'alreadyDeleted' => true];
        }

        $logModel = new TransferLogModel();
        $logId = (int) $logModel->insert([
            'document_id' => $documentId,
            'user_id' => $userId,
            'action' => 'delete_hosting',
            'status' => 'started',
            'started_at' => date('Y-m-d H:i:s'),
        ], true);

        try {
            $localPath = $this->resolveLocalPath((string) $document['local_path']);
            if ($localPath === null) {
                throw new RuntimeException('File lokal tidak ditemukan. File hosting tidak dihapus.');
            }
            $bytes = filesize($localPath);
            $checksum = hash_file('sha256', $localPath);
            if ($bytes === false || ! is_string($checksum)
                || $bytes !== (int) $document['file_size']
                || ! hash_equals((string) $document['sha256_checksum'], $checksum)) {
                throw new RuntimeException('Integritas file lokal berubah. File hosting tidak dihapus.');
            }

            $body = json_encode([
                'sha256_checksum' => $checksum,
                'file_size' => $bytes,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $remotePath = str_replace('{id}', (string) (int) $document['remote_document_id'], $this->config->deletePath);
            $response = $this->request('POST', $remotePath, $body);
            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException($this->remoteError($response, 'menghapus file hosting'));
            }

            $payload = json_decode((string) $response->getBody(), true);
            $remoteDeletedAt = is_array($payload) ? ($payload['data']['deleted_at'] ?? null) : null;
            $deletedAt = $this->dateTime($remoteDeletedAt) ?? date('Y-m-d H:i:s');
            $alreadyDeleted = is_array($payload) && (($payload['data']['already_deleted'] ?? false) === true);
            $documents->update($documentId, [
                'hosting_deleted_at' => $deletedAt,
                'hosting_delete_error' => null,
            ]);
            $logModel->update($logId, [
                'status' => 'success',
                'http_status' => $response->getStatusCode(),
                'checksum_valid' => 1,
                'finished_at' => date('Y-m-d H:i:s'),
            ]);

            return ['deletedAt' => $deletedAt, 'alreadyDeleted' => $alreadyDeleted];
        } catch (Throwable $exception) {
            $documents->update($documentId, [
                'hosting_delete_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            $logModel->update($logId, [
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            throw $exception;
        }
    }

    private function request(string $method, string $path, string $body = '', string $accept = 'application/json'): ResponseInterface
    {
        $url = $this->url($path);
        $urlPath = parse_url($url, PHP_URL_PATH);
        if (! is_string($urlPath) || $urlPath === '') {
            throw new RuntimeException('URL API hosting tidak valid.');
        }
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $signature = StorageSyncSignature::sign(
            $this->config->secret,
            $this->config->clientId,
            $method,
            $urlPath,
            $timestamp,
            $nonce,
            $body,
        );
        $headers = [
            'X-Sync-Client' => $this->config->clientId,
            'X-Sync-Timestamp' => $timestamp,
            'X-Sync-Nonce' => $nonce,
            'X-Sync-Signature' => $signature,
            'Accept' => $accept,
        ];
        $options = ['headers' => $headers];
        if ($body !== '') {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = $body;
        }

        return $this->client->request(strtoupper($method), $url, $options);
    }

    private function assertConfigured(): void
    {
        if (! $this->config->isConfigured()) {
            throw new RuntimeException('Koneksi hosting belum dikonfigurasi pada file .env.');
        }
    }

    private function url(string $path): string
    {
        return $this->config->baseUrl . '/' . ltrim($path, '/');
    }

    private function resolveLocalPath(string $relativePath): ?string
    {
        $root = realpath(WRITEPATH . 'documents');
        if ($root === false) {
            return null;
        }
        $path = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/')));
        if ($path === false || ! is_file($path)
            || ! str_starts_with(mb_strtolower($path), mb_strtolower($root . DIRECTORY_SEPARATOR))) {
            return null;
        }

        return $path;
    }

    private function remoteError(ResponseInterface $response, string $action): string
    {
        $payload = json_decode((string) $response->getBody(), true);
        $detail = is_array($payload) && is_string($payload['message'] ?? null) ? ' ' . $payload['message'] : '';

        return 'Hosting merespons HTTP ' . $response->getStatusCode() . ' saat ' . $action . '.' . $detail;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value >= 0 ? (int) $value : null;
    }

    private function limited(mixed $value, int $length): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    private function checksum(mixed $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));
        return preg_match('/^[a-f0-9]{64}$/', $value) === 1 ? $value : null;
    }

    private function dateTime(mixed $value): ?string
    {
        $timestamp = strtotime((string) $value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
