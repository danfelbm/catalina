<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReporteMadurezCategoria extends Model
{
    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'reporte_madurez_categorias';

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'nombre',
        'codigo',
        'orden',
        'color',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'orden' => 'integer',
    ];

    /**
     * Relación: Una categoría tiene muchos elementos
     */
    public function elementos(): HasMany
    {
        return $this->hasMany(ReporteMadurezElemento::class, 'categoria_id')->orderBy('orden');
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }
}
