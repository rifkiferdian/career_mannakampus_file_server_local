<?php

namespace App\Services;

use App\Models\DocumentModel;
use App\Models\TransferLogModel;
use CodeIgniter\HTTP\CURLRequest;
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
        ]);
    }

    public function syncMetadata(): int
    {
        $this->assertConfigured();
        $response = $this->client->get($this->url($this->config->pendingPath), [
            'headers' => $this->headers(),
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Hosting merespons HTTP ' . $response->getStatusCode() . ' saat mengambil daftar dokumen.');
        }

        $payload = json_decode($response->getBody(), true);
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
                'application_number' => $this->limited($row['application_number'] ?? null, 50),
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

        $startedAt = date('Y-m-d H:i:s');
        $logModel = new TransferLogModel();
        $logId = (int) $logModel->insert([
            'document_id' => $documentId,
            'user_id' => $userId,
            'action' => $document['transfer_status'] === 'failed' ? 'retry' : 'download',
            'status' => 'started',
            'started_at' => $startedAt,
        ], true);
        $documents->update($documentId, ['transfer_status' => 'downloading', 'last_error' => null]);

        try {
            $path = str_replace('{id}', (string) (int) $document['remote_document_id'], $this->config->downloadPath);
            $response = $this->client->get($this->url($path), ['headers' => $this->headers()]);
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new RuntimeException('Hosting merespons HTTP ' . $statusCode . ' saat mengunduh file.');
            }

            $body = $response->getBody();
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
            if ($expected !== null && ! hash_equals($expected, $checksum)) {
                throw new RuntimeException('Checksum file tidak cocok. File tidak disimpan.');
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
                'last_error' => null,
                'downloaded_at' => date('Y-m-d H:i:s'),
                'downloaded_by' => $userId,
            ]);

            $confirmed = $this->confirm((int) $document['remote_document_id'], $checksum, $bytes);
            $logModel->update($logId, [
                'status' => 'success',
                'http_status' => $statusCode,
                'bytes_received' => $bytes,
                'checksum_valid' => 1,
                'error_message' => $confirmed ? null : 'File tersimpan, tetapi hosting belum menerima konfirmasi.',
                'finished_at' => date('Y-m-d H:i:s'),
            ]);

            return ['confirmed' => $confirmed, 'bytes' => $bytes, 'checksum' => $checksum];
        } catch (Throwable $exception) {
            $documents->update($documentId, ['transfer_status' => 'failed', 'last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
            $logModel->update($logId, [
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            throw $exception;
        }
    }

    private function confirm(int $remoteId, string $checksum, int $bytes): bool
    {
        try {
            $path = str_replace('{id}', (string) $remoteId, $this->config->confirmPath);
            $response = $this->client->post($this->url($path), [
                'headers' => $this->headers() + ['Content-Type' => 'application/json'],
                'json' => ['sha256_checksum' => $checksum, 'file_size' => $bytes, 'downloaded_at' => date(DATE_ATOM)],
            ]);

            return in_array($response->getStatusCode(), [200, 204], true);
        } catch (Throwable) {
            return false;
        }
    }

    private function assertConfigured(): void
    {
        if (! $this->config->isConfigured()) {
            throw new RuntimeException('Koneksi hosting belum dikonfigurasi pada file .env.');
        }
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->config->apiKey, 'Accept' => 'application/json'];
    }

    private function url(string $path): string
    {
        return $this->config->baseUrl . '/' . ltrim($path, '/');
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
