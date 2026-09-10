<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use App\Services\AuditService;
use App\Services\RemoteDocumentService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use Config\RemoteStorage;
use Throwable;

class DocumentController extends BaseController
{
    private const DOCUMENTS_PER_PAGE = 50;

    public function index(): string
    {
        $status = trim((string) $this->request->getGet('status'));
        $storage = trim((string) $this->request->getGet('storage'));
        $search = trim((string) $this->request->getGet('q'));
        $model = new DocumentModel();
        if (in_array($status, ['pending', 'downloading', 'completed', 'failed'], true)) {
            $model->where('transfer_status', $status);
        }
        if ($storage === 'ready') {
            $model->where('transfer_status', 'completed')
                ->where('confirmation_status', 'confirmed')
                ->where('hosting_deleted_at', null);
        } elseif ($storage === 'deleted') {
            $model->where('hosting_deleted_at IS NOT NULL', null, false);
        } elseif ($storage === 'attention') {
            $model->groupStart()
                ->whereIn('transfer_status', ['pending', 'downloading', 'failed'])
                ->orGroupStart()
                    ->where('transfer_status', 'completed')
                    ->whereIn('confirmation_status', ['pending', 'failed'])
                ->groupEnd()
            ->groupEnd();
        }
        if ($search !== '') {
            $model->groupStart()
                ->like('applicant_name', $search)
                ->orLike('application_number', $search)
                ->orLike('original_filename', $search)
                ->groupEnd();
        }

        $documents = $model
            ->orderBy('remote_uploaded_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(self::DOCUMENTS_PER_PAGE);
        $pager = $model->pager;
        $currentPage = max(1, $pager->getCurrentPage());
        $summary = [
            'total' => (new DocumentModel())->countAllResults(),
            'readyToDelete' => (new DocumentModel())
                ->where('transfer_status', 'completed')
                ->where('confirmation_status', 'confirmed')
                ->where('hosting_deleted_at', null)
                ->countAllResults(),
            'deletedFromHosting' => (new DocumentModel())
                ->where('hosting_deleted_at IS NOT NULL', null, false)
                ->countAllResults(),
            'needsAttention' => (new DocumentModel())
                ->groupStart()
                    ->whereIn('transfer_status', ['pending', 'downloading', 'failed'])
                    ->orGroupStart()
                        ->where('transfer_status', 'completed')
                        ->whereIn('confirmation_status', ['pending', 'failed'])
                    ->groupEnd()
                ->groupEnd()
                ->countAllResults(),
        ];

        return view('documents/index', [
            'title' => 'Dokumen Pelamar',
            'documents' => $documents,
            'pager' => $pager,
            'rowNumberStart' => (($currentPage - 1) * self::DOCUMENTS_PER_PAGE) + 1,
            'summary' => $summary,
            'status' => $status,
            'storage' => in_array($storage, ['ready', 'deleted', 'attention'], true) ? $storage : '',
            'search' => $search,
            'remoteConfigured' => config(RemoteStorage::class)->isConfigured(),
        ]);
    }

    public function sync()
    {
        $auth = (array) session('auth_user');
        try {
            $result = (new RemoteDocumentService())->syncAll((int) $auth['id']);
            $summary = sprintf(
                'Metadata: %d, PDF tersimpan: %d, konfirmasi hosting: %d, gagal: %d.',
                $result['metadata'],
                $result['downloaded'],
                $result['confirmed'],
                $result['failed'],
            );
            (new AuditService())->record('documents_synced', $summary);
            if ($result['failed'] > 0) {
                return redirect()->back()->with('warning', 'Sinkronisasi selesai dengan sebagian kegagalan. ' . $summary);
            }

            return redirect()->back()->with('success', 'Sinkronisasi selesai. ' . $summary);
        } catch (Throwable $exception) {
            (new AuditService())->record('documents_sync_failed', mb_substr($exception->getMessage(), 0, 500));
            return redirect()->back()->with('error', 'Sinkronisasi gagal: ' . $exception->getMessage());
        }
    }

    public function download(int $id)
    {
        $auth = (array) session('auth_user');
        try {
            $result = (new RemoteDocumentService())->download($id, (int) $auth['id']);
            (new AuditService())->record('document_downloaded', 'Dokumen berhasil disimpan dan diverifikasi.', $id);
            if (! $result['confirmed']) {
                return redirect()->back()->with('warning', 'File sudah tersimpan dan checksum valid, tetapi konfirmasi ke hosting belum berhasil. File hosting belum boleh dihapus.');
            }
            return redirect()->back()->with('success', 'Dokumen berhasil diunduh, disimpan, dan dikonfirmasi ke hosting.');
        } catch (Throwable $exception) {
            (new AuditService())->record('document_download_failed', mb_substr($exception->getMessage(), 0, 500), $id);
            return redirect()->back()->with('error', 'Download gagal: ' . $exception->getMessage());
        }
    }

    public function deleteHosting(int $id)
    {
        $auth = (array) session('auth_user');
        try {
            $result = (new RemoteDocumentService())->deleteFromHosting($id, (int) $auth['id']);
            $message = $result['alreadyDeleted']
                ? 'File di hosting sebelumnya sudah dihapus. Salinan lokal tetap tersedia.'
                : 'File PDF berhasil dihapus dari hosting. Salinan lokal tetap tersedia.';
            (new AuditService())->record('hosting_file_deleted', $message, $id);

            return redirect()->back()->with('success', $message);
        } catch (Throwable $exception) {
            (new AuditService())->record('hosting_file_delete_failed', mb_substr($exception->getMessage(), 0, 500), $id);

            return redirect()->back()->with('error', 'File hosting tidak dihapus: ' . $exception->getMessage());
        }
    }

    public function deleteHostingBulk()
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $this->request->getPost('document_ids')),
            static fn (int $id): bool => $id > 0,
        )));
        if ($ids === []) {
            return redirect()->back()->with('warning', 'Pilih minimal satu dokumen yang akan dihapus dari hosting.');
        }
        if (count($ids) > 50) {
            return redirect()->back()->with('error', 'Maksimal 50 dokumen dapat dihapus dalam satu proses.');
        }

        $auth = (array) session('auth_user');
        $service = new RemoteDocumentService();
        $success = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $service->deleteFromHosting($id, (int) $auth['id']);
                (new AuditService())->record('hosting_file_deleted', 'File PDF dihapus dari hosting melalui pilihan massal.', $id);
                $success++;
            } catch (Throwable $exception) {
                (new AuditService())->record('hosting_file_delete_failed', mb_substr($exception->getMessage(), 0, 500), $id);
                $failed++;
            }
        }

        $summary = sprintf('%d file hosting dihapus; %d gagal.', $success, $failed);
        if ($failed > 0) {
            return redirect()->back()->with('warning', $summary . ' Periksa status atau riwayat transfer.');
        }

        return redirect()->back()->with('success', $summary . ' Salinan lokal tetap tersimpan.');
    }

    public function open(int $id): DownloadResponse
    {
        $document = (new DocumentModel())->find($id);
        if ($document === null || $document['transfer_status'] !== 'completed' || empty($document['local_path'])) {
            throw PageNotFoundException::forPageNotFound('Dokumen lokal tidak ditemukan.');
        }
        $root = realpath(WRITEPATH . 'documents');
        $path = $root === false ? false : realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim((string) $document['local_path'], '/')));
        if ($root === false || $path === false || ! is_file($path)
            || ! str_starts_with(mb_strtolower($path), mb_strtolower($root . DIRECTORY_SEPARATOR))) {
            throw PageNotFoundException::forPageNotFound('File dokumen lokal tidak tersedia.');
        }
        if (! hash_equals((string) $document['sha256_checksum'], hash_file('sha256', $path))) {
            throw PageNotFoundException::forPageNotFound('Integritas file dokumen tidak valid.');
        }

        (new AuditService())->record('document_opened', 'Dokumen lokal dibuka.', $id);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $document['original_filename']) ?: 'dokumen.pdf';

        return $this->response->download($path, null, true)
            ->setFileName($filename)
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0');
    }
}
