<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHostingDeletionStatusToDocuments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('documents', [
            'hosting_deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'remote_confirmed_at',
            ],
            'hosting_delete_error' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'hosting_deleted_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('documents', ['hosting_deleted_at', 'hosting_delete_error']);
    }
}
