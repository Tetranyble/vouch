<?php

declare(strict_types=1);

namespace Tests\Unit\Storage;

use App\Services\DocumentStorage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentStorageReferenceTest extends TestCase
{
    #[Test]
    public function only_canonical_media_identifiers_are_treated_as_package_records(): void
    {
        $storage = app(DocumentStorage::class);

        foreach (['media:1', 'media:1234'] as $reference) {
            $this->assertTrue($storage->isMediaReference($reference));
        }
        foreach (['media:0', 'media:-2', 'media:../../secret', 'media:1/2', '', 'vouch/old.pdf'] as $reference) {
            $this->assertFalse($storage->isMediaReference($reference));
        }
    }
}
