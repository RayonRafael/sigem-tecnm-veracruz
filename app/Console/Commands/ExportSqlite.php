<?php

// TODO: comando temporal para demo.

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportSqlite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sigem:export-sqlite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporta datos desde la base de datos MySQL a la base de datos SQLite embebida';

    /**
     * Tablas a exportar en orden de dependencias de claves foráneas.
     */
    protected array $tables = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'edificios',
        'departamento',
        'area',
        'salones',
        'marca_material',
        'tipo_material',
        'unidad_medida',
        'proveedores',
        'receptor',
        'material',
        'inventario',
        'solicitud',
        'detalle_solicitud',
        'mantenimiento',
        'historial_estados',
        'activity_log',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando exportación de datos de MySQL a SQLite...');

        $mysql = DB::connection('mysql');
        $sqlite = DB::connection('sqlite');

        // Desactivar temporalmente foreign keys en SQLite para evitar bloqueos
        $sqlite->statement('PRAGMA foreign_keys = OFF;');

        $summary = [];

        foreach ($this->tables as $table) {
            if (!Schema::connection('mysql')->hasTable($table)) {
                $this->warn("Tabla '{$table}' no encontrada en MySQL. Omitiendo...");
                continue;
            }

            if (!Schema::connection('sqlite')->hasTable($table)) {
                $this->warn("Tabla '{$table}' no encontrada en SQLite. Omitiendo...");
                continue;
            }

            // REGLA: Verificar columnas existentes antes de insertar
            $sqliteColumns = Schema::connection('sqlite')->getColumnListing($table);
            $sqliteColumnsMap = array_flip($sqliteColumns);

            // Limpiar datos existentes en SQLite
            $sqlite->table($table)->delete();

            $rows = $mysql->table($table)->get();
            $totalSource = $rows->count();
            $successCount = 0;
            $failCount = 0;

            foreach ($rows as $row) {
                $rowData = (array) $row;
                // Filtrar solo las columnas que existen en la tabla SQLite
                $filteredData = array_intersect_key($rowData, $sqliteColumnsMap);

                try {
                    $sqlite->table($table)->insert($filteredData);
                    $successCount++;
                } catch (\Throwable $e) {
                    $failCount++;
                    $this->warn("Error insertando registro en '{$table}': " . $e->getMessage());
                }
            }

            $currentSqliteCount = $sqlite->table($table)->count();

            $summary[] = [
                'Tabla' => $table,
                'MySQL' => $totalSource,
                'Insertados' => $successCount,
                'Fallidos' => $failCount,
                'Total SQLite' => $currentSqliteCount,
            ];

            $this->line("Exportada tabla <info>{$table}</info>: {$successCount}/{$totalSource} registros.");
        }

        // Reactivar foreign keys en SQLite
        $sqlite->statement('PRAGMA foreign_keys = ON;');

        $this->newLine();
        $this->table(['Tabla', 'MySQL', 'Insertados', 'Fallidos', 'Total SQLite'], $summary);
        $this->info('Exportación completada exitosamente.');

        return Command::SUCCESS;
    }
}
