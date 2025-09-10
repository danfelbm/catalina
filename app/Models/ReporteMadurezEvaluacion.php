<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteMadurezEvaluacion extends Model
{
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'reporte_madurez_evaluaciones';

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'reporte_id',
        'elemento_id',
        'nivel',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'reporte_id' => 'integer',
        'elemento_id' => 'integer',
    ];

    /**
     * Niveles de madurez disponibles
     */
    const NIVELES = [
        'emergente' => 'EMERGENTE',
        'resolutivo' => 'RESOLUTIVO',
        'laborioso' => 'LABORIOSO',
        'cooperativo' => 'COOPERATIVO',
        'progresivo' => 'PROGRESIVO',
    ];

    /**
     * Relación: Una evaluación pertenece a un reporte
     */
    public function reporte(): BelongsTo
    {
        return $this->belongsTo(ReporteMadurez::class, 'reporte_id');
    }

    /**
     * Relación: Una evaluación pertenece a un elemento
     */
    public function elemento(): BelongsTo
    {
        return $this->belongsTo(ReporteMadurezElemento::class, 'elemento_id');
    }

    /**
     * Scope para filtrar por nivel
     */
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    /**
     * Scope para filtrar por reporte
     */
    public function scopePorReporte($query, $reporteId)
    {
        return $query->where('reporte_id', $reporteId);
    }

    /**
     * Scope para filtrar por elemento
     */
    public function scopePorElemento($query, $elementoId)
    {
        return $query->where('elemento_id', $elementoId);
    }

    /**
     * Obtener el nombre del nivel en formato legible
     */
    public function getNivelNombreAttribute(): string
    {
        return self::NIVELES[$this->nivel] ?? $this->nivel;
    }

    /**
     * Validar si un nivel es válido
     */
    public static function esNivelValido(string $nivel): bool
    {
        return array_key_exists($nivel, self::NIVELES);
    }
}
