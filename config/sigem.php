<?php
// config/sigem.php

return [
    // Datos institucionales
    'institucion' => [
        'nombre' => 'Instituto Tecnológico de Veracruz',
        'tecnm' => 'Tecnológico Nacional de México',
        'sep' => 'Secretaría de Educación Pública',
        'direccion' => 'Calz. Miguel Ángel de Quevedo 2779 Col. Formando Hogar, Veracruz, Ver. C.P. 91800',
        'telefono' => '(229) 934 15 00',
        'email' => 'veracruz@veracruz.tecnm.mx',
        'web' => 'web.veracruz.tecnm.mx',
        'ciudad' => 'H. Veracruz, Veracruz',
    ],

    // Centro de Cómputo
    'centro_computo' => [
        'nombre' => 'Centro de Cómputo',
        'jefe' => '', // Nombre del jefe (se llena desde departamento.encargado)
    ],

    // Subdirección (datos institucionales fijos)
    'subdireccion' => [
        'nombre' => 'Subdirección de Planeación y Vinculación',
        'responsable' => '', // Nombre del subdirector
    ],

    // Oficina de Almacén e Inventario (para formato baja)
    'almacen' => [
        'subdireccion' => 'Subdirección de Servicios Administrativos',
        'departamento' => 'Depto. de Recursos Materiales y Servicios',
        'oficina' => 'Oficina de Almacén e Inventario',
    ],

    // Recursos Materiales y Servicios (para formato mantenimiento)
    'recursos_materiales' => [
        'nombre' => 'Recursos Materiales y Servicios',
    ],

    // Normas ISO (para formato mantenimiento)
    'normas' => [
        'iso_9001' => 'ISO 9001: 2015',
        'iso_14001' => 'ISO 14001: 2015',
        'referencia_9001' => '6.1, 7.1, 7.2, 7.4, 7.5.1, 8.1',
        'referencia_14001' => '4.1, 6.1, 8.1, 8.2',
    ],

    // Logos institucionales
    'logos' => [
        'sep' => 'images/sep-logo.png',
        'tecnm' => 'images/tecnm-logo.png',
        'itv' => 'images/itv-logo.png',
        'sigem' => 'images/sigem-logo.svg',
        'iso_9001' => 'images/iso-9001.png',
        'iso_14001' => 'images/iso-14001.png',
    ],

    // PDF defaults
    'pdf' => [
        'tamaño' => 'letter',
        'orientacion' => 'portrait',
        'margen_top' => '15mm',
        'margen_bottom' => '15mm',
        'margen_left' => '15mm',
        'margen_right' => '15mm',
    ],
];

