<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReporteMadurezElemento extends Model
{
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'reporte_madurez_elementos';

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'categoria_id',
        'numero',
        'nombre',
        'orden',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'categoria_id' => 'integer',
        'numero' => 'integer',
        'orden' => 'integer',
    ];

    /**
     * Relación: Un elemento pertenece a una categoría
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(ReporteMadurezCategoria::class, 'categoria_id');
    }

    /**
     * Relación: Un elemento tiene muchas evaluaciones
     */
    public function evaluaciones(): HasMany
    {
        return $this->hasMany(ReporteMadurezEvaluacion::class, 'elemento_id');
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }
}
