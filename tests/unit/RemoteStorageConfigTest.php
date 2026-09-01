<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\RemoteStorage;

final class RemoteStorageConfigTest extends CIUnitTestCase
{
    public function testRemoteStorageIsNotConfiguredWithoutCredentials(): void
    {
        $config = new RemoteStorage();

        $this->assertFalse($config->isConfigured());
        $this->assertSame('api/storage/documents/pending', $config->pendingPath);
        $this->assertStringContainsString('{id}', $config->downloadPath);
        $this->assertGreaterThanOrEqual(2_097_152, $config->maxFileSize);
    }
}
