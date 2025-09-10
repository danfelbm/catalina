<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReporteMadurez extends Model
{
    use HasTenant;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'reporte_madurez';

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'empresa',
        'ciudad',
        'centro_trabajo',
        'area',
        'fecha_realizacion',
        'tenant_id',
        'created_by',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'fecha_realizacion' => 'date',
        'tenant_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Relación: Un reporte pertenece a un usuario creador
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación: Un reporte tiene muchas evaluaciones
     */
    public function evaluaciones(): HasMany
    {
        return $this->hasMany(ReporteMadurezEvaluacion::class, 'reporte_id');
    }

    /**
     * Scope para reportes por fecha
     */
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_realizacion', $fecha);
    }

    /**
     * Scope para reportes por empresa
     */
    public function scopePorEmpresa($query, $empresa)
    {
        return $query->where('empresa', 'like', "%{$empresa}%");
    }

    /**
     * Scope para reportes por ciudad
     */
    public function scopePorCiudad($query, $ciudad)
    {
        return $query->where('ciudad', 'like', "%{$ciudad}%");
    }

    /**
     * Obtener el porcentaje de completitud del reporte
     */
    public function getPorcentajeCompletitudAttribute(): float
    {
        $totalElementos = ReporteMadurezElemento::count();
        $elementosEvaluados = $this->evaluaciones()->count();
        
        if ($totalElementos === 0) {
            return 0;
        }
        
        return round(($elementosEvaluados / $totalElementos) * 100, 2);
    }

    /**
     * Obtener estadísticas de evaluación del reporte
     */
    public function getEstadisticas()
    {
        $estadisticas = [
            'por_nivel' => [],
            'por_categoria' => [],
            'total_evaluado' => 0,
            'total_elementos' => ReporteMadurezElemento::count(),
            'porcentaje_completitud' => $this->porcentaje_completitud,
        ];

        // Estadísticas por nivel
        $niveles = ['emergente', 'resolutivo', 'laborioso', 'cooperativo', 'progresivo'];
        foreach ($niveles as $nivel) {
            $estadisticas['por_nivel'][$nivel] = $this->evaluaciones()
                ->where('nivel', $nivel)
                ->count();
        }

        // Estadísticas por categoría
        $categorias = ReporteMadurezCategoria::with(['elementos.evaluaciones' => function($query) {
            $query->where('reporte_id', $this->id);
        }])->ordenado()->get();

        foreach ($categorias as $categoria) {
            $totalElementos = $categoria->elementos->count();
            $elementosEvaluados = $categoria->elementos->filter(function($elemento) {
                return $elemento->evaluaciones->count() > 0;
            })->count();

            // Calcular distribución por niveles en esta categoría
            $distribucionNiveles = [
                'emergente' => 0,
                'resolutivo' => 0,
                'laborioso' => 0,
                'cooperativo' => 0,
                'progresivo' => 0,
            ];

            foreach ($categoria->elementos as $elemento) {
                if ($elemento->evaluaciones->count() > 0) {
                    $nivel = $elemento->evaluaciones->first()->nivel;
                    if (array_key_exists($nivel, $distribucionNiveles)) {
                        $distribucionNiveles[$nivel]++;
                    }
                }
            }

            $estadisticas['por_categoria'][$categoria->codigo] = [
                'nombre' => $categoria->nombre,
                'color' => $categoria->color,
                'total_elementos' => $totalElementos,
                'elementos_evaluados' => $elementosEvaluados,
                'porcentaje' => $totalElementos > 0 ? round(($elementosEvaluados / $totalElementos) * 100, 2) : 0,
                'distribucion_niveles' => $distribucionNiveles,
            ];
        }

        $estadisticas['total_evaluado'] = $this->evaluaciones()->count();

        return $estadisticas;
    }
}
