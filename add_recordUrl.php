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
            
            // If already contains ->recordUrl
            if (str_contains($content, '->recordUrl(false)')) {
                continue;
            }
            if (preg_match('/->recordUrl\([^)]*\)/', $content)) {
                $content = preg_replace('/->recordUrl\([^)]*\)/', '->recordUrl(false)', $content);
                file_put_contents($path, $content);
                echo "Updated existing recordUrl: $path\n";
                continue;
            }

            // Inject ->recordUrl(false) right before ->actions([
            $newContent = preg_replace('/(\n\s*)(->actions\(\[)/', "$1->recordUrl(false)$1$2", $content, 1);

            if ($content !== $newContent) {
                file_put_contents($path, $newContent);
                echo "Updated: $path\n";
            } else {
                echo "Failed to find ->actions([ in $path\n";
            }
        }
    }
}

foreach ($dirs as $dir) {
    processDir($dir);
}
echo "Done.\n";
