<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BitacoraSistema;
use Spatie\Activitylog\Models\Activity;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\User;

class MigrateBitacora extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-bitacora';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos de bitacora_sistema a la tabla activity_log de Spatie';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando migración de bitacora_sistema a activity_log...');

        $bitacoras = BitacoraSistema::all();
        $total = $bitacoras->count();
        $migrados = 0;
        $errores = 0;

        if ($total === 0) {
            $this->info('No hay registros para migrar.');
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($bitacoras as $b) {
            try {
                $subjectType = match ($b->tabla_afectada) {
                    'inventario' => Inventario::class,
                    'mantenimiento' => Mantenimiento::class,
                    'solicitud' => Solicitud::class,
                    'material' => Material::class,
                    'proveedor' => Proveedor::class,
                    default => null,
                };

                // Manejo de JSON seguro por si el modelo no los castea a array
                $oldData = is_string($b->datos_anteriores) ? json_decode($b->datos_anteriores, true) : $b->datos_anteriores;
                $newData = is_string($b->datos_nuevos) ? json_decode($b->datos_nuevos, true) : $b->datos_nuevos;

                Activity::create([
                    'log_name' => 'default',
                    'description' => $b->detalles ?? $b->accion,
                    'event' => $b->accion,
                    'subject_type' => $subjectType,
                    'subject_id' => $b->registro_id,
                    'causer_type' => User::class,
                    'causer_id' => $b->id_usuario,
                    'properties' => [
                        'old' => $oldData,
                        'attributes' => $newData,
                    ],
                    'created_at' => $b->fecha_hora,
                    'updated_at' => $b->fecha_hora,
                ]);

                $migrados++;
            } catch (\Exception $e) {
                $errores++;
                $this->error("\nError migrando registro ID " . $b->getKey() . ": " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();

        $this->info("\n\nMigración completada.");
        $this->info("Total registros encontrados: {$total}");
        $this->info("Registros migrados exitosamente: {$migrados}");
        
        if ($errores > 0) {
            $this->error("Hubo {$errores} errores durante la migración.");
        }
    }
}
