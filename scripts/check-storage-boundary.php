<?php

declare(strict_types=1);

/**
 * Source-level invariant: application document bytes always use our scoped
 * Tetranyble/storage adapter and never Laravel's raw Storage facade.
 * The test harness may use Storage::fake to emulate the underlying filesystem.
 */
$root = dirname(__DIR__);
$sourceRoots = [$root.'/app', $root.'/routes'];
$errors = [];
$checked = 0;
foreach ($sourceRoots as $sourceRoot) {
    $app = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($app as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $checked++;
    $content = file_get_contents($file->getPathname());
    $display = substr($file->getPathname(), strlen($root) + 1);
    if (preg_match('~(?:use\\s+Illuminate\\\\Support\\\\Facades\\\\Storage\\s*(?:as\\s+\\w+)?\\s*;|use\\s+App\\\\Facades\\\\DocumentStorage\\s+as\\s+Storage\\s*;|(?<![a-zA-Z])\\\\?Storage::)~', $content)) {
        $errors[] = "$display uses a raw/misleading Storage facade";
    }

    // Simple method-call scanner: these read/mutate package media and MUST be
    // passed a workspace id. A missing accountId should fail even with syntax-valid PHP.
    preg_match_all('~(?:DocumentStorage::|\\$this->documents->|\\$storage->)(exists|get|size|mimeType|url|download|delete|findMedia)\\s*\\(~', $content, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[1] as $i => [$method]) {
        $open = $matches[0][$i][1] + strlen($matches[0][$i][0]) - 1;
        $depth = 0;
        $commaAtTopLevel = false;
        $n = strlen($content);
        for ($j = $open; $j < $n; $j++) {
            $char = $content[$j];
            if ($char === '(') $depth++;
            if ($char === ')') {
                $depth--;
                if ($depth === 0) break;
            }
            if ($char === ',' && $depth === 1) $commaAtTopLevel = true;
        }
        if (!$commaAtTopLevel) {
            $line = substr_count(substr($content, 0, $open), "\n") + 1;
            $errors[] = "$display:$line $method() is missing the required account scope";
        }
    }
    }
}
if ($errors) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}
fwrite(STDOUT, "PASS: $checked application PHP files respect scoped Tetranyble storage boundary.\n");
