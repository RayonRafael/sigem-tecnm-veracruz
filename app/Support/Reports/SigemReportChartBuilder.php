<?php

namespace App\Support\Reports;

class SigemReportChartBuilder
{
    /**
     * Construye la estructura de datos compatible con Chart.js para los reportes visuales.
     */
    public static function build(array $meta, array $headers, array $rows): ?array
    {
        if (empty($meta['has_chart'])) {
            return null;
        }

        $type = $meta['chart_type'] ?? 'bar';
        $labels = [];
        $data = [];
        $id = $meta['id'] ?? '';

        switch ($id) {
            case 'estadisticas_propio_vs_rentado':
                foreach ($rows as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'inventario_por_estado':
            case 'inventario_por_tipo':
                foreach (array_slice($rows, 0, 7) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'inventario_por_departamento':
            case 'estadisticas_depto_mas_equipos':
                // Incluir TODOS los departamentos (incluso los de 0 equipos) sin recortar en 8
                foreach ($rows as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'mantenimiento_depto_pide_mas':
                foreach (array_slice($rows, 0, 12) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'mantenimiento_por_mes':
                foreach (array_slice($rows, 0, 12) as $r) {
                    $labels[] = trim(($r[0] ?? '') . ' ' . ($r[1] ?? ''));
                    $data[] = (int) ($r[2] ?? 0);
                }
                break;

            case 'rentas_proximas_vencer':
                $r15 = 0;
                $r30 = 0;
                $r60 = 0;
                foreach ($rows as $r) {
                    $dias = (int) preg_replace('/[^0-9]/', '', (string) ($r[4] ?? '0'));
                    if ($dias <= 15) {
                        $r15++;
                    } elseif ($dias <= 30) {
                        $r30++;
                    } else {
                        $r60++;
                    }
                }
                $labels = ['1 a 15 días', '16 a 30 días', '31 a 90 días'];
                $data = [$r15, $r30, $r60];
                break;

            case 'rentas_gasto_mensual':
                foreach (array_slice($rows, 0, 12) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $val = preg_replace('/[^0-9.]/', '', (string) ($r[2] ?? ($r[1] ?? 0)));
                    $data[] = (float) $val;
                }
                break;

            case 'estadisticas_mas_movidos':
                foreach (array_slice($rows, 0, 8) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[2] ?? 0);
                }
                break;

            case 'historico_cambios_area':
                $porMes = [];
                foreach ($rows as $r) {
                    $f = (string) ($r[0] ?? '');
                    $mesKey = strlen($f) >= 10 ? substr($f, 3, 7) : 'General';
                    $porMes[$mesKey] = ($porMes[$mesKey] ?? 0) + 1;
                }
                if (empty($porMes)) {
                    $labels = ['Sin datos'];
                    $data = [0];
                } else {
                    $labels = array_keys($porMes);
                    $data = array_values($porMes);
                }
                break;

            case 'historico_actividad_usuario':
                foreach (array_slice($rows, 0, 6) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            default:
                foreach (array_slice($rows, 0, 6) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = isset($r[1]) && is_numeric($r[1]) ? (int) $r[1] : 1;
                }
                break;
        }

        if (empty($labels)) {
            $labels = ['Sin registros'];
            $data = [0];
        }

        $isPieOrDonut = in_array($type, ['pie', 'doughnut'], true);
        if ($isPieOrDonut) {
            $palette = $meta['chart_colors'] ?? ['#1b65d4', '#059669', '#d97706', '#dc2626', '#8b5cf6', '#0891b2', '#64748b'];
            $bgColors = [];
            foreach ($labels as $idx => $lbl) {
                $bgColors[] = $palette[$idx % count($palette)];
            }
        } else {
            $bgColors = $meta['chart_color'] ?? '#1b65d4';
        }

        $chartTitle = $meta['chart_title'] ?? ($meta['titulo'] ?? 'Reporte');
        $chartUnit = $meta['chart_unit'] ?? 'registros';

        return [
            'type' => $type,
            'chart_title' => $chartTitle,
            'chart_unit' => $chartUnit,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $chartTitle,
                    'data' => $data,
                    'backgroundColor' => $bgColors,
                    'borderColor' => $isPieOrDonut ? '#ffffff' : ($meta['chart_color'] ?? '#1b65d4'),
                    'borderWidth' => $isPieOrDonut ? 2 : 1.5,
                    'fill' => $type === 'line' ? false : true,
                    'tension' => 0.35,
                ],
            ],
            'options' => [
                'indexAxis' => ! empty($meta['chart_horizontal']) ? 'y' : 'x',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => $chartTitle,
                        'align' => 'start',
                        'color' => '#0f172a',
                        'font' => [
                            'size' => 15,
                            'weight' => '600',
                        ],
                        'padding' => [
                            'top' => 4,
                            'bottom' => 14,
                        ],
                    ],
                    'legend' => [
                        'display' => $isPieOrDonut,
                        'position' => 'bottom',
                        'labels' => ['boxWidth' => 10, 'font' => ['size' => 11]],
                    ],
                    'tooltip' => [
                        'enabled' => true,
                    ],
                ],
                'scales' => $isPieOrDonut ? (object) [] : [
                    'y' => ['beginAtZero' => true, 'grid' => ['color' => '#f1f5f9']],
                    'x' => ['grid' => ['display' => false]],
                ],
            ],
        ];
    }
}
