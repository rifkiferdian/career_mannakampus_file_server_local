<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentConfirmationStatus extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('documents', [
            'confirmation_status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'confirmed', 'failed'],
                'default' => 'pending',
                'after' => 'transfer_status',
            ],
            'confirmation_error' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'confirmation_status',
            ],
            'remote_confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'confirmation_error',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `documents` ADD INDEX `documents_confirmation_idx` '
            . '(`transfer_status`, `confirmation_status`)',
        );
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `documents` DROP INDEX `documents_confirmation_idx`');
        $this->forge->dropColumn('documents', [
            'confirmation_status',
            'confirmation_error',
            'remote_confirmed_at',
        ]);
    }
}
