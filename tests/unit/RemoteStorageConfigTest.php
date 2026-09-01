<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\RemoteStorage;

final class RemoteStorageConfigTest extends CIUnitTestCase
{
    public function testRemoteStorageConfigurationAndPaths(): void
    {
        $config = new RemoteStorage();

        $this->assertTrue($config->isConfigured());
        $this->assertSame('manna-local-01', $config->clientId);
        $this->assertSame('api/storage/documents/pending', $config->pendingPath);
        $this->assertStringContainsString('{id}', $config->downloadPath);
        $this->assertGreaterThanOrEqual(2_097_152, $config->maxFileSize);
    }
}
