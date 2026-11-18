<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\TemporaryDocumentFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemporaryDocumentFileTest extends TestCase
{
    #[Test]
    public function it_writes_the_full_content_to_a_file_with_the_needed_suffix(): void
    {
        $file = TemporaryDocumentFile::create("%PDF-1.4\n%%EOF", 'vouch_test_', 'pdf');
        try {
            $this->assertFileExists($file);
            $this->assertStringEndsWith('.pdf', $file);
            $this->assertSame("%PDF-1.4\n%%EOF", file_get_contents($file));
        } finally {
            @unlink($file);
        }
    }

    #[Test]
    public function it_rejects_untrusted_extensions(): void
    {
        $this->expectException(\RuntimeException::class);
        TemporaryDocumentFile::create('not persisted', 'vouch_test_', '../php');
    }
}
