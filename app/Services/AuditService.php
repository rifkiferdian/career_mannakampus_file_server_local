<?php

namespace App\Services;

use App\Models\AuditLogModel;

class AuditService
{
    public function record(string $event, ?string $description = null, ?int $documentId = null, ?int $userId = null): void
    {
        $request = service('request');
        $sessionUser = (array) session('auth_user');

        (new AuditLogModel())->insert([
            'user_id' => $userId ?? (isset($sessionUser['id']) ? (int) $sessionUser['id'] : null),
            'document_id' => $documentId,
            'event' => $event,
            'description' => $description,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => mb_substr((string) $request->getUserAgent(), 0, 500),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
