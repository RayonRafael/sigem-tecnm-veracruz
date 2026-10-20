<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Material extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'stock_minimo', 'id_marca', 'id_tipodematerial', 'id_unidad', 'requiere_control_individual'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $acciones = [
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                ];

                return $acciones[$eventName] ?? $eventName;
            });
    }

    protected $table = 'material';

    protected $primaryKey = 'id_producto';

    protected $fillable = [
        'nombre', 'descripcion', 'modelo',
        'id_unidad', 'id_marca', 'id_tipodematerial',
        'requiere_control_individual', 'stock_minimo',
    ];

    // RELACIONES: Pertenece a una unidad, marca y tipo
    public function unidad()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad', 'id_unidad');
    }

    public function marca()
    {
        return $this->belongsTo(MarcaMaterial::class, 'id_marca', 'id_marca');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoMaterial::class, 'id_tipodematerial', 'id_tipodematerial');
    }

    // RELACIONES: Un material tiene muchos inventarios
    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_producto', 'id_producto');
    }

    /**
     * SQL (correlacionado con la tabla `material`) que calcula el stock derivado SIN recortar a 0.
     *
     *   stock = piezas de inventario del material que estan 'Disponible', con estado_registro
     *           'Aprobado' y no eliminadas (soft delete)
     *         - unidades comprometidas en solicitudes 'Autorizado' de 'Asignacion Permanente'
     *           que aun no salen fisicamente del conteo anterior:
     *             * detalles SIN id_inventario (se comprometen por cantidad), y
     *             * detalles CON id_inventario cuya pieza sigue 'Disponible' y aprobada
     *               (si la pieza ya cambio de estado, deja de contar en el primer termino,
     *               por lo que NO se resta otra vez: evita doble descuento).
     *
     * Al ser derivado, cancelar/rechazar/borrar una solicitud o editar cantidades se refleja solo.
     */
    public static function stockDerivadoSql(): string
    {
        return "(
            (SELECT COUNT(*) FROM inventario i
                WHERE i.id_producto = material.id_producto
                  AND i.estado = 'Disponible'
                  AND i.estado_registro = 'Aprobado'
                  AND i.deleted_at IS NULL)
            -
            (SELECT COALESCE(SUM(d.cantidad), 0) FROM detalle_solicitud d
                INNER JOIN solicitud s ON s.id_solicitud = d.id_solicitud
                LEFT JOIN inventario di ON di.id_inventario = d.id_inventario
                WHERE d.id_producto = material.id_producto
                  AND s.estado = 'Autorizado'
                  AND s.tipo_movimiento = 'Asignacion Permanente'
                  AND (d.id_inventario IS NULL
                       OR (di.estado = 'Disponible'
                           AND di.estado_registro = 'Aprobado'
                           AND di.deleted_at IS NULL)))
        )";
    }

    /**
     * Stock derivado (la columna material.stock_actual queda obsoleta y se ignora).
     * Nunca devuelve negativo. Nota: calcula una consulta por instancia.
     */
    protected function stockActual(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $id = $attributes[$this->primaryKey] ?? null;
                if (! $id) {
                    return 0;
                }

                $row = DB::selectOne(
                    'SELECT '.self::stockDerivadoSql().' AS stock FROM material WHERE material.id_producto = ?',
                    [$id]
                );

                return max(0, (int) ($row->stock ?? 0));
            },
        );
    }

    // Scopes
    public function scopeStockBajo($query)
    {
        // Se usa el SQL derivado: la columna stock_actual ya no es fuente de verdad.
        return $query->whereRaw(self::stockDerivadoSql().' < material.stock_minimo');
    }
}
