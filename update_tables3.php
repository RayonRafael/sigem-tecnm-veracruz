<?php

$dirs = [
    __DIR__ . '/app/Filament/Resources/',
    __DIR__ . '/app/Filament/ServicioSocial/Resources/'
];

$nonEssentialColumns = [
    'created_at', 'updated_at', 'deleted_at', 
    'apellido_paterno', 'apellido_materno', 
    'email', 'telefono', 'rfc', 'contacto_nombre', 'contacto_telefono', 'contacto_email',
    'descripcion', 'observaciones', 'stock_minimo', 'fecha_devolucion_estimada', 'modelo', 'tipo_mantenimiento'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = glob($dir . '*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // 4 & 5. Add ->striped() and default pagination to all tables
        if (strpos($content, '->striped()') === false) {
            $content = str_replace('->filters([', "->striped()\n            ->defaultPaginationPageOption(10)\n            ->filters([", $content);
        }
        
        // 1 & 3. TextColumns: wrap(false), limit(30), grow(false)
        $content = preg_replace_callback('/Tables\\\\Columns\\\\([A-Za-z]+Column)::make\(\'([^\']+)\'\)/', function($matches) use ($nonEssentialColumns) {
            $colType = $matches[1];
            $colName = $matches[2];
            
            $injection = "";
            
            if ($colType === 'TextColumn') {
                $injection .= "->wrap(false)->limit(30)";
            }
            
            $injection .= "->grow(false)";
            
            // 2. Non-essential columns
            $isNonEssential = false;
            foreach ($nonEssentialColumns as $nec) {
                if ($colName === $nec) {
                    $isNonEssential = true;
                    break;
                }
            }
            
            if ($isNonEssential) {
                $injection .= "->toggleable(isToggledHiddenByDefault: true)";
            }
            
            return "Tables\\Columns\\" . $colType . "::make('" . $colName . "')" . $injection;
        }, $content);
        
        // Clean up any double toggleable if they already existed (unlikely after reset, but just in case)
        $content = str_replace('->toggleable(isToggledHiddenByDefault: true)->toggleable(isToggledHiddenByDefault: true)', '->toggleable(isToggledHiddenByDefault: true)', $content);
        
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
