<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/** @method static string upload(\Illuminate\Http\UploadedFile $file, int $accountId, string $purpose, ?int $userId = null, array $properties = []) */
/** @method static string putContent(string $content, string $filename, int $accountId, string $purpose, ?int $userId = null, array $properties = []) */
/** @method static bool exists(?string $reference, int $accountId) */
/** @method static string get(string $reference, int $accountId) */
/** @method static int size(string $reference, int $accountId) */
/** @method static ?string mimeType(string $reference, int $accountId) */
/** @method static string url(string $reference, int $accountId) */
/** @method static \Symfony\Component\HttpFoundation\Response download(string $reference, int $accountId, ?string $filename = null) */
/** @method static bool delete(string $reference, int $accountId) */
/** @see \App\Services\DocumentStorage */
final class DocumentStorage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\DocumentStorage::class;
    }
}
