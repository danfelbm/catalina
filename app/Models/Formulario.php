<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Formulario extends Model
{
    use HasFactory, HasTenant;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'formularios';

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'titulo',
        'descripcion',
        'slug',
        'categoria_id',
        'configuracion_campos',
        'configuracion_general',
        'tipo_acceso',
        'permite_visitantes',
        'requiere_captcha',
        'fecha_inicio',
        'fecha_fin',
        'limite_respuestas',
        'limite_por_usuario',
        'estado',
        'activo',
        'emails_notificacion',
        'mensaje_confirmacion',
        'creado_por',
        'actualizado_por',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'configuracion_campos' => 'array',
        'configuracion_general' => 'array',
        'emails_notificacion' => 'array',
        'permite_visitantes' => 'boolean',
        'requiere_captcha' => 'boolean',
        'activo' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'limite_respuestas' => 'integer',
        'limite_por_usuario' => 'integer',
    ];

    /**
     * Los atributos que deben ser mutados a fechas.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'fecha_inicio',
        'fecha_fin',
    ];

    /**
     * Boot del modelo para eventos.
     */
    protected static function boot()
    {
        parent::boot();

        // Generar slug automáticamente al crear
        static::creating(function ($formulario) {
            if (empty($formulario->slug)) {
                $formulario->slug = Str::slug($formulario->titulo);
                
                // Asegurar que el slug sea único
                $count = static::where('slug', 'LIKE', $formulario->slug . '%')
                    ->where('tenant_id', $formulario->tenant_id)
                    ->count();
                    
                if ($count > 0) {
                    $formulario->slug = $formulario->slug . '-' . ($count + 1);
                }
            }
        });
    }

    /**
     * Obtener la categoría del formulario.
     */
    public function categoria()
    {
        return $this->belongsTo(FormularioCategoria::class, 'categoria_id');
    }

    /**
     * Obtener las respuestas del formulario.
     */
    public function respuestas()
    {
        return $this->hasMany(FormularioRespuesta::class);
    }

    /**
     * Obtener los permisos del formulario.
     */
    public function permisos()
    {
        return $this->hasMany(FormularioPermiso::class);
    }

    /**
     * Obtener el usuario que creó el formulario.
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Obtener el usuario que actualizó el formulario.
     */
    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Scope para formularios activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para formularios publicados.
     */
    public function scopePublicados($query)
    {
        return $query->where('estado', 'publicado');
    }

    /**
     * Scope para formularios vigentes.
     */
    public function scopeVigentes($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('fecha_inicio')
              ->orWhere('fecha_inicio', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('fecha_fin')
              ->orWhere('fecha_fin', '>=', $now);
        });
    }

    /**
     * Verificar si el formulario está vigente.
     */
    public function estaVigente(): bool
    {
        $now = now();
        
        if ($this->fecha_inicio && $this->fecha_inicio > $now) {
            return false;
        }
        
        if ($this->fecha_fin && $this->fecha_fin < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Verificar si el formulario está disponible para llenar.
     */
    public function estaDisponible(): bool
    {
        // Debe estar publicado, activo y vigente
        if ($this->estado !== 'publicado' || !$this->activo) {
            return false;
        }
        
        if (!$this->estaVigente()) {
            return false;
        }
        
        // Verificar límite de respuestas
        if ($this->limite_respuestas && $this->respuestas()->where('estado', 'enviado')->count() >= $this->limite_respuestas) {
            return false;
        }
        
        return true;
    }

    /**
     * Verificar si un usuario puede llenar el formulario.
     */
    public function puedeSerLlenadoPor($usuario = null): bool
    {
        if (!$this->estaDisponible()) {
            return false;
        }
        
        // Si es público, cualquiera puede llenarlo
        if ($this->tipo_acceso === 'publico') {
            return true;
        }
        
        // Si requiere autenticación y no hay usuario
        if ($this->tipo_acceso === 'autenticado' && !$usuario) {
            return $this->permite_visitantes;
        }
        
        // Si requiere permiso específico
        if ($this->tipo_acceso === 'con_permiso' && $usuario) {
            // Verificar si el usuario tiene permiso específico
            return $this->tienePermiso($usuario, 'llenar');
        }
        
        // Si hay usuario y requiere autenticación
        if ($usuario && $this->tipo_acceso === 'autenticado') {
            // Verificar límite por usuario
            if ($this->limite_por_usuario) {
                $respuestasUsuario = $this->respuestas()
                    ->where('usuario_id', $usuario->id)
                    ->where('estado', 'enviado')
                    ->count();
                    
                return $respuestasUsuario < $this->limite_por_usuario;
            }
            return true;
        }
        
        return false;
    }

    /**
     * Verificar si un usuario tiene un permiso específico.
     */
    public function tienePermiso($usuario, $tipoPermiso): bool
    {
        if (!$usuario) {
            return false;
        }
        
        // Verificar permisos por usuario
        $permisoUsuario = $this->permisos()
            ->where('usuario_id', $usuario->id)
            ->where('tipo_permiso', $tipoPermiso)
            ->where('activo', true)
            ->where(function ($q) {
                $q->whereNull('valido_desde')
                  ->orWhere('valido_desde', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valido_hasta')
                  ->orWhere('valido_hasta', '>=', now());
            })
            ->exists();
            
        if ($permisoUsuario) {
            return true;
        }
        
        // Verificar permisos por rol
        foreach ($usuario->roles as $rol) {
            $permisoRol = $this->permisos()
                ->where('role_id', $rol->id)
                ->where('tipo_permiso', $tipoPermiso)
                ->where('activo', true)
                ->where(function ($q) {
                    $q->whereNull('valido_desde')
                      ->orWhere('valido_desde', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('valido_hasta')
                      ->orWhere('valido_hasta', '>=', now());
                })
                ->exists();
                
            if ($permisoRol) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtener estadísticas del formulario.
     */
    public function getEstadisticas(): array
    {
        $respuestas = $this->respuestas()->where('estado', 'enviado');
        
        return [
            'total_respuestas' => $respuestas->count(),
            'respuestas_hoy' => $respuestas->whereDate('created_at', today())->count(),
            'respuestas_semana' => $respuestas->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'respuestas_mes' => $respuestas->whereMonth('created_at', now()->month)->count(),
            'usuarios_unicos' => $respuestas->distinct('usuario_id')->count('usuario_id'),
            'visitantes_unicos' => $respuestas->whereNull('usuario_id')->distinct('email_visitante')->count('email_visitante'),
        ];
    }

    /**
     * Obtener estadísticas por categorías/dimensiones.
     */
    public function getEstadisticasPorCategoria(): array
    {
        $respuestas = $this->respuestas()->where('estado', 'enviado')->get();
        $campos = $this->configuracion_campos;
        
        if ($respuestas->isEmpty() || empty($campos)) {
            return [];
        }

        // Agrupar campos por categoría
        $camposPorCategoria = [];
        foreach ($campos as $campo) {
            if (isset($campo['category']) && !empty($campo['category']['name'])) {
                $categoriaId = $campo['category']['id'] ?? 'sin_categoria';
                $categoriaNombre = $campo['category']['name'];
                
                if (!isset($camposPorCategoria[$categoriaId])) {
                    $camposPorCategoria[$categoriaId] = [
                        'nombre' => $categoriaNombre,
                        'descripcion' => $campo['category']['description'] ?? null,
                        'campos' => []
                    ];
                }
                
                $camposPorCategoria[$categoriaId]['campos'][] = $campo;
            }
        }

        $estadisticasPorCategoria = [];

        foreach ($camposPorCategoria as $categoriaId => $categoriaInfo) {
            $valoresCategoria = [];
            $totalRespuestas = 0;
            $sumaTotal = 0;

            foreach ($categoriaInfo['campos'] as $campo) {
                $campoId = $campo['id'];
                
                foreach ($respuestas as $respuesta) {
                    $respuestasData = $respuesta->respuestas ?? [];
                    if (isset($respuestasData[$campoId])) {
                        $valor = $respuestasData[$campoId];
                        
                        // Si el valor es numérico, usarlo directamente
                        if (is_numeric($valor)) {
                            $valoresCategoria[] = (float)$valor;
                            $sumaTotal += (float)$valor;
                            $totalRespuestas++;
                        }
                        // Si no es numérico, intentar mapear según las opciones del campo
                        elseif (isset($campo['options'])) {
                            foreach ($campo['options'] as $opcion) {
                                if (is_array($opcion) && isset($opcion['value']) && 
                                    ((isset($opcion['label']) && $opcion['label'] == $valor) || $opcion['value'] == $valor)) {
                                    $valoresCategoria[] = (float)$opcion['value'];
                                    $sumaTotal += (float)$opcion['value'];
                                    $totalRespuestas++;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if ($totalRespuestas > 0) {
                $promedio = $sumaTotal / $totalRespuestas;
                
                // Calcular distribución de valores
                $distribucion = [];
                for ($i = 1; $i <= 4; $i++) {
                    $count = count(array_filter($valoresCategoria, function($v) use ($i) {
                        return abs($v - $i) < 0.01; // Comparación con tolerancia para flotantes
                    }));
                    $distribucion[$i] = [
                        'valor' => $i,
                        'count' => $count,
                        'porcentaje' => $totalRespuestas > 0 ? round(($count / $totalRespuestas) * 100, 2) : 0
                    ];
                }

                $estadisticasPorCategoria[$categoriaId] = [
                    'id' => $categoriaId,
                    'nombre' => $categoriaInfo['nombre'],
                    'descripcion' => $categoriaInfo['descripcion'],
                    'promedio' => round($promedio, 2),
                    'total_respuestas' => $totalRespuestas,
                    'total_preguntas' => count($categoriaInfo['campos']),
                    'distribucion' => $distribucion,
                    'valores_raw' => $valoresCategoria, // Para análisis adicional si es necesario
                ];
            }
        }

        // Ordenar por ID de categoría para mantener consistencia
        ksort($estadisticasPorCategoria);

        return $estadisticasPorCategoria;
    }

    /**
     * Obtener datos para gráfico de radar por categorías.
     */
    public function getDatosRadarPorCategoria(): array
    {
        $estadisticas = $this->getEstadisticasPorCategoria();
        
        $labels = [];
        $promedios = [];
        
        foreach ($estadisticas as $categoria) {
            $labels[] = $categoria['nombre'];
            $promedios[] = $categoria['promedio'];
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Promedio por Dimensión',
                    'data' => $promedios,
                    'borderColor' => 'rgb(59, 130, 246)', // blue-500
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderWidth' => 2,
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointRadius' => 4,
                ]
            ],
            'options' => [
                'scales' => [
                    'r' => [
                        'min' => 1,
                        'max' => 4,
                        'stepSize' => 0.5,
                        'ticks' => [
                            'display' => true
                        ]
                    ]
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom'
                    ]
                ]
            ]
        ];
    }

    /**
     * Obtener el estado temporal del formulario.
     */
    public function getEstadoTemporal(): string
    {
        if ($this->estado === 'borrador') {
            return 'borrador';
        }
        
        if ($this->estado === 'archivado') {
            return 'archivado';
        }
        
        if (!$this->activo) {
            return 'inactivo';
        }
        
        $now = now();
        
        if ($this->fecha_inicio && $this->fecha_inicio > $now) {
            return 'programado';
        }
        
        if ($this->fecha_fin && $this->fecha_fin < $now) {
            return 'finalizado';
        }
        
        if ($this->limite_respuestas && $this->respuestas()->where('estado', 'enviado')->count() >= $this->limite_respuestas) {
            return 'lleno';
        }
        
        return 'activo';
    }

    /**
     * Obtener el label del estado temporal.
     */
    public function getEstadoTemporalLabel(): string
    {
        return match($this->getEstadoTemporal()) {
            'borrador' => 'Borrador',
            'archivado' => 'Archivado',
            'inactivo' => 'Inactivo',
            'programado' => 'Programado',
            'finalizado' => 'Finalizado',
            'lleno' => 'Lleno',
            'activo' => 'Activo',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener el color del estado temporal.
     */
    public function getEstadoTemporalColor(): string
    {
        return match($this->getEstadoTemporal()) {
            'borrador' => 'gray',
            'archivado' => 'slate',
            'inactivo' => 'red',
            'programado' => 'blue',
            'finalizado' => 'orange',
            'lleno' => 'yellow',
            'activo' => 'green',
            default => 'gray',
        };
    }

    /**
     * Obtener estadísticas por pregunta individual.
     */
    public function getEstadisticasPorPregunta(): array
    {
        $respuestas = $this->respuestas()->where('estado', 'enviado')->get();
        $campos = $this->configuracion_campos;
        
        if ($respuestas->isEmpty() || empty($campos)) {
            return [];
        }

        $estadisticasPorPregunta = [];
        
        foreach ($campos as $campo) {
            // Saltar campos que no son de rating/escala
            if (!in_array($campo['type'], ['rating', 'scale', 'select', 'radio']) && 
                !isset($campo['options'])) {
                continue;
            }

            $campoId = $campo['id'];
            $preguntaTexto = $campo['title'] ?? 'Sin título';
            
            // Obtener la categoría/dimensión si existe
            $categoriaNombre = '';
            if (isset($campo['category']) && !empty($campo['category']['name'])) {
                $categoriaNombre = $campo['category']['name'];
            }

            $valores = [];
            foreach ($respuestas as $respuesta) {
                $valor = $respuesta->respuestas[$campoId] ?? null;
                
                if ($valor !== null && is_numeric($valor)) {
                    $valores[] = (float)$valor;
                }
            }

            if (!empty($valores)) {
                $suma = array_sum($valores);
                $count = count($valores);
                $promedio = $suma / $count;

                // Calcular distribución de respuestas
                $distribucion = [];
                $rangos = [1, 2, 3, 4, 5]; // Escala típica 1-5
                
                foreach ($rangos as $i) {
                    $countRango = count(array_filter($valores, function($v) use ($i) {
                        return $v == $i;
                    }));
                    
                    if ($countRango > 0) { // Solo incluir rangos que tienen datos
                        $distribucion[$i] = [
                            'valor' => $i,
                            'count' => $countRango,
                            'porcentaje' => round(($countRango / $count) * 100, 2)
                        ];
                    }
                }

                $estadisticasPorPregunta[] = [
                    'campo_id' => $campoId,
                    'pregunta' => $preguntaTexto,
                    'dimension' => $categoriaNombre,
                    'promedio' => round($promedio, 2),
                    'total_respuestas' => $count,
                    'suma_total' => $suma,
                    'distribucion' => $distribucion,
                    'valores_min_max' => [
                        'min' => min($valores),
                        'max' => max($valores),
                    ],
                ];
            }
        }

        // Ordenar por dimensión y luego por campo_id para mantener orden lógico
        usort($estadisticasPorPregunta, function($a, $b) {
            if ($a['dimension'] === $b['dimension']) {
                return strcmp($a['campo_id'], $b['campo_id']);
            }
            return strcmp($a['dimension'], $b['dimension']);
        });

        return $estadisticasPorPregunta;
    }
}