<?php

$dirs = [
    __DIR__ . '/app/Filament/Resources',
    __DIR__ . '/app/Filament/ServicioSocial/Resources'
];

$replacements = [
    // Secciones / Wizards
    "'Datos del Área'" => "'Datos del área'",
    "'Datos del Departamento'" => "'Datos del departamento'",
    "'Datos del Material'" => "'Datos del material'",
    "'Datos del Receptor'" => "'Datos del receptor'",
    "'Datos del Solicitante'" => "'Datos del solicitante'",
    "'Datos de la Empresa'" => "'Datos de la empresa'",
    "'Información de Contacto'" => "'Información de contacto'",
    "'Identificación y Ubicación'" => "'Identificación y ubicación'",
    "'Equipo y Técnico'" => "'Equipo y técnico'",
    "'Servicio y Detalles'" => "'Servicio y detalles'",
    "'Estado y Rol'" => "'Estado y rol'",
    "'Datos Institucionales'" => "'Datos institucionales'",
    "'Datos de Acceso'" => "'Datos de acceso'",
    "'Datos Personales'" => "'Datos personales'",
    "'Datos y Clasificación'" => "'Datos y clasificación'",
    "'Control de Stock'" => "'Control de stock'",
    "'Datos de la Marca'" => "'Datos de la marca'",
    "'Datos del Tipo'" => "'Datos del tipo'",
    "'Datos de la Unidad'" => "'Datos de la unidad'",

    // Labels
    "'Nombre del Área'" => "'Nombre del área'",
    "'Nombre del Departamento'" => "'Nombre del departamento'",
    "'Apellido Paterno'" => "'Apellido paterno'",
    "'Apellido Materno'" => "'Apellido materno'",
    "'Correo Electrónico'" => "'Correo electrónico'",
    "'Tipo de Material'" => "'Tipo de material'",
    "'Unidad de Medida'" => "'Unidad de medida'",
    "'Número de Serie'" => "'Número de serie'",
    "'Ubicación Física'" => "'Ubicación física'",
    "'Tipo de Propiedad'" => "'Tipo de propiedad'",
    "'Nombre de la Empresa'" => "'Nombre de la empresa'",
    "'Nombre del Contacto'" => "'Nombre del contacto'",
    "'Teléfono del Contacto'" => "'Teléfono del contacto'",
    "'Email del Contacto'" => "'Email del contacto'",
    "'Número de Factura'" => "'Número de factura'",
    "'Fecha de Adquisición'" => "'Fecha de adquisición'",
    "'Valor Estimado'" => "'Valor estimado'",
    "'Técnico / Alumno'" => "'Técnico / alumno'",
    "'Número de Control Técnico'" => "'Número de control técnico'",
    "'Número de Control'" => "'Número de control'",
    "'Tipo de Servicio'" => "'Tipo de servicio'",
    "'Tipo de Mantenimiento'" => "'Tipo de mantenimiento'",
    "'Fecha de Solicitud'" => "'Fecha de solicitud'",
    "'Fecha de Inicio'" => "'Fecha de inicio'",
    "'Fecha de Fin'" => "'Fecha de fin'",
    "'Descripción de la Falla'" => "'Descripción de la falla'",
    "'Trabajo Realizado'" => "'Trabajo realizado'",
    "'Tipo de Usuario'" => "'Tipo de usuario'",
    "'Usuario Activo'" => "'Usuario activo'",
    "'Stock Actual'" => "'Stock actual'",
    "'Stock Mínimo'" => "'Stock mínimo'",
    "'Requiere Control Individual'" => "'Requiere control individual'",
    "'Rol Spatie'" => "'Rol Spatie'",
    "'Usuario Activo'" => "'Usuario activo'",
    "'Nombre de la marca'" => "'Nombre de la marca'", // already lower
    "'Nombre del tipo de material'" => "'Nombre del tipo de material'",
    "'Nombre de la unidad de medida'" => "'Nombre de la unidad de medida'",
];

function processDir($dir, $replacements) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            processDir($path, $replacements);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            $newContent = strtr($content, $replacements);
            
            // Add hasRecordTitle if not exists
            if (!str_contains($newContent, 'function hasRecordTitle()')) {
                // Find the end of the class, or we can just inject it before the last closing brace
                // A better place is right before `public static function form`
                $pattern = '/(public static function form\(Form \$form\): Form)/';
                $replacement = "public static function hasRecordTitle(): bool\n    {\n        return false;\n    }\n\n    $1";
                $newContent = preg_replace($pattern, $replacement, $newContent);
            }

            if ($content !== $newContent) {
                file_put_contents($path, $newContent);
                echo "Updated: $path\n";
            }
        }
    }
}

foreach ($dirs as $dir) {
    processDir($dir, $replacements);
}

echo "Done.\n";
