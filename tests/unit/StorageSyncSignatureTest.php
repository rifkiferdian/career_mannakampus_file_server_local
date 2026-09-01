<?php

use App\Libraries\StorageSyncSignature;
use CodeIgniter\Test\CIUnitTestCase;

final class StorageSyncSignatureTest extends CIUnitTestCase
{
    public function testSignatureChangesWhenBodyChanges(): void
    {
        $arguments = [
            'secret-with-at-least-thirty-two-characters',
            'manna-local-01',
            'POST',
            '/api/storage/documents/10/confirm',
            '1788242400',
            '0123456789abcdef0123456789abcdef',
        ];

        $first = StorageSyncSignature::sign(...[...$arguments, '{"file_size":100}']);
        $second = StorageSyncSignature::sign(...[...$arguments, '{"file_size":101}']);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first);
        $this->assertNotSame($first, $second);
    }
}
