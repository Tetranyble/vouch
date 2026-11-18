<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Ephemeral local files for third-party PDF tools that require filenames.
 * Persistent Vouch documents must still be written through DocumentStorage.
 * Every caller must unlink the returned path in a finally block.
 */
final class TemporaryDocumentFile
{
    public static function create(string $contents, string $prefix = 'vouch_', string $extension = ''): string
    {
        if ($extension !== '' && ! preg_match('/^[a-z0-9]{1,8}$/D', $extension)) {
            throw new RuntimeException('Invalid temporary file extension.');
        }

        $path = tempnam(sys_get_temp_dir(), $prefix);
        if ($path === false) {
            throw new RuntimeException('Unable to create temporary document file.');
        }

        try {
            if ($extension !== '') {
                $target = $path.'.'.$extension;
                if (! rename($path, $target)) {
                    throw new RuntimeException('Unable to prepare temporary document filename.');
                }
                $path = $target;
            }

            $written = file_put_contents($path, $contents, LOCK_EX);
            if ($written === false || $written !== strlen($contents)) {
                throw new RuntimeException('Unable to write complete temporary document.');
            }

            return $path;
        } catch (\Throwable $error) {
            @unlink($path);
            throw $error;
        }
    }
}
