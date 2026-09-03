<?php

$dirs = [
    __DIR__ . '/app/Filament/Resources',
    __DIR__ . '/app/Filament/ServicioSocial/Resources'
];

function processDir($dir) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            processDir($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            
            // Add recordAction(null) if it's not there
            if (!str_contains($content, '->recordAction(null)')) {
                $newContent = str_replace('->recordUrl(null)', '->recordUrl(null)' . "\n            " . '->recordAction(null)', $content);
                if ($content !== $newContent) {
                    file_put_contents($path, $newContent);
                    echo "Updated with recordAction(null): $path\n";
                }
            }
        }
    }
}

foreach ($dirs as $dir) {
    processDir($dir);
}
echo "Done.\n";
