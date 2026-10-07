<?php

$dir = __DIR__ . '/app/Models';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;

    // Fix primary key - using single quotes for regex string to prevent PHP variable interpolation
    $content = preg_replace(
        '/protected\s+\\$primaryKey\s*=\s*\'[a-zA-Z0-9_]+\';/',
        "protected \$primaryKey = '_id';\n    protected \$keyType = 'string';",
        $content
    );

    if (basename($file) === 'User.php') {
        $content = preg_replace("/protected\s+\\$primaryKey\s*=\s*'_id';\s*protected\s+\\$keyType\s*=\s*'string';\s*protected\s+\\$keyType\s*=\s*'string';/", "protected \$primaryKey = '_id';\n    protected \$keyType = 'string';", $content);
    }

    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Refactored primary key: " . basename($file) . "\n";
    }
}

echo "Primary keys updated.\n";
