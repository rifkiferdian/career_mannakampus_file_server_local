<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLocalFileServerTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 50],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role' => ['type' => 'ENUM', 'constraint' => ['admin', 'hrd'], 'default' => 'hrd'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'must_change_password' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'remote_document_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'applicant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'application_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'applicant_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'document_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'application_bundle'],
            'original_filename' => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_filename' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'local_path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'application/pdf'],
            'file_size' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'sha256_checksum' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'transfer_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'downloading', 'completed', 'failed'], 'default' => 'pending'],
            'last_error' => ['type' => 'TEXT', 'null' => true],
            'remote_uploaded_at' => ['type' => 'DATETIME', 'null' => true],
            'downloaded_at' => ['type' => 'DATETIME', 'null' => true],
            'downloaded_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('remote_document_id');
        $this->forge->addKey('transfer_status');
        $this->forge->addKey('application_number');
        $this->forge->addForeignKey('downloaded_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('documents');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'document_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 30],
            'status' => ['type' => 'ENUM', 'constraint' => ['started', 'success', 'failed']],
            'http_status' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'bytes_received' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'checksum_valid' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'started_at' => ['type' => 'DATETIME'],
            'finished_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('started_at');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('transfer_logs');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 190],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_success' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'attempted_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['username', 'ip_address', 'attempted_at']);
        $this->forge->createTable('login_attempts');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'document_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'event' => ['type' => 'VARCHAR', 'constraint' => 60],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['event', 'created_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('audit_logs');
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('login_attempts', true);
        $this->forge->dropTable('transfer_logs', true);
        $this->forge->dropTable('documents', true);
        $this->forge->dropTable('users', true);
    }
}
