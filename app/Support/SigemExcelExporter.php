<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SigemExcelExporter
{
    /**
     * Exporta un reporte a un archivo nativo .xlsx (Excel OpenXML) sin dependencias externas.
     *
     * @param string $titulo
     * @param array $headers
     * @param array $rows
     * @param string $fileName
     * @return BinaryFileResponse
     */
    public static function export(string $titulo, array $headers, array $rows, string $fileName): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sigem_xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $sheetTitle = mb_substr(preg_replace('/[\\\\\/\?\*\[\]\:]/', '', $titulo), 0, 31);
        if (empty($sheetTitle)) {
            $sheetTitle = 'Reporte';
        }
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="' . htmlspecialchars($sheetTitle, ENT_XML1) . '" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $wb);

        // 5. xl/styles.xml (Estilo con encabezado azul institucional TecNM)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="2">' .
            '<font><name val="Calibri"/><sz val="11"/></font>' .
            '<font><b/><name val="Calibri"/><sz val="11"/><color rgb="FFFFFFFF"/></font>' .
            '</fonts>' .
            '<fills count="3">' .
            '<fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FF1B396A"/></patternFill></fill>' . // Azul TecNM #1B396A
            '</fills>' .
            '<borders count="1"><border/></borders>' .
            '<cellStyleXfs count="1"><xf/></cellStyleXfs>' .
            '<cellXfs count="2">' .
            '<xf fontId="0" fillId="0" borderId="0" xfId="0"/>' .
            '<xf fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>' .
            '</cellXfs>' .
            '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetData>';

        $rowIdx = 1;
        // Fila de encabezados
        $sheetXml .= '<row r="' . $rowIdx . '">';
        foreach (array_values($headers) as $colIdx => $header) {
            $colLetter = self::columnIndexToLetter($colIdx);
            $cleanText = htmlspecialchars(trim(strip_tags((string) $header)), ENT_XML1);
            $sheetXml .= '<c r="' . $colLetter . $rowIdx . '" t="inlineStr" s="1"><is><t>' . $cleanText . '</t></is></c>';
        }
        $sheetXml .= '</row>';

        // Filas de datos
        foreach ($rows as $row) {
            $rowIdx++;
            $sheetXml .= '<row r="' . $rowIdx . '">';
            foreach (array_values((array) $row) as $colIdx => $cell) {
                $colLetter = self::columnIndexToLetter($colIdx);
                $cleanText = htmlspecialchars(trim(strip_tags((string) $cell)), ENT_XML1);
                $sheetXml .= '<c r="' . $colLetter . $rowIdx . '" t="inlineStr"><is><t>' . $cleanText . '</t></is></c>';
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        return response()->download($tmp, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ])->deleteFileAfterSend(true);
    }

    public static function exportarInventario($livewire = null): BinaryFileResponse
    {
        $query = ($livewire && method_exists($livewire, 'getFilteredTableQuery'))
            ? $livewire->getFilteredTableQuery()
            : \App\Models\Inventario::query();

        $registros = $query
            ->with(['material.tipo', 'departamento', 'area.departamento', 'salon'])
            ->latest('created_at')
            ->get();

        $headers = [
            'Departamento',
            'Código',
            'Equipo',
            'Categoría',
            'Estado',
            'Área',
            'Aula',
            'No. de serie',
            'IP',
            'MAC',
        ];

        $rows = [];
        foreach ($registros as $record) {
            $depto = $record->departamento?->nombre
                ?? $record->area?->departamento?->nombre
                ?? ($record->ubicacion_fisica ? (explode(' > ', $record->ubicacion_fisica)[0] ?? null) : null)
                ?? '—';

            $codigo = ! empty($record->codigo)
                ? $record->codigo
                : ($record->num_serie ?: ('#' . ($record->id_inventario ?? $record->id)));

            $equipo = $record->material?->nombre ?? $record->subtipo ?? 'Equipo';
            $categoria = $record->material?->tipo?->nombre ?? $record->subtipo ?? 'General';
            $estado = $record->estado ?? 'Disponible';

            $area = $record->area?->nombre;
            if (! $area && ! empty($record->ubicacion_fisica)) {
                $parts = explode(' > ', $record->ubicacion_fisica);
                $area = $parts[1] ?? $parts[0] ?? null;
            }
            $area = $area ?: '—';

            $aula = $record->salon?->nombre
                ?? ($record->especificaciones['aula'] ?? null)
                ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                ?? '—';

            $numSerie = $record->num_serie ?: '—';
            $ip = $record->ip_address ?? ($record->especificaciones['ip_address'] ?? null) ?? '—';
            $mac = $record->mac_address ?? ($record->especificaciones['mac_address'] ?? null) ?? '—';

            $rows[] = [
                $depto,
                $codigo,
                $equipo,
                $categoria,
                $estado,
                $area,
                $aula,
                $numSerie,
                $ip,
                $mac,
            ];
        }

        if (empty($rows)) {
            $rows[] = ['Sin registros', '—', '—', '—', '—', '—', '—', '—', '—', '—'];
        }

        $fileName = 'inventario-sigem-' . now()->format('Y-m-d-His') . '.xlsx';

        return self::export(
            'Inventario de Equipos y Materiales',
            $headers,
            $rows,
            $fileName
        );
    }

    public static function columnIndexToLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(($index % 26) + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }

        return $letter;
    }
}

