<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Formulario;
use App\Models\FormularioCategoria;
use App\Models\FormularioRespuesta;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class FormularioController extends Controller
{
    use HasAdvancedFilters;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Formulario::with(['categoria', 'creador']);

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'titulo', 'descripcion', 'slug', 'categoria_id',
            'tipo_acceso', 'estado', 'activo',
            'fecha_inicio', 'fecha_fin',
            'limite_respuestas', 'limite_por_usuario',
            'created_at', 'updated_at'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['titulo', 'descripcion', 'slug'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        // Ordenamiento
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $formularios = $query->paginate(15)->withQueryString();

        // Enriquecer datos con información adicional
        $formularios->getCollection()->transform(function ($formulario) {
            $estadisticas = $formulario->getEstadisticas();
            
            return [
                'id' => $formulario->id,
                'titulo' => $formulario->titulo,
                'descripcion' => $formulario->descripcion,
                'slug' => $formulario->slug,
                'tipo_acceso' => $formulario->tipo_acceso,
                'estado' => $formulario->estado,
                'activo' => $formulario->activo,
                'fecha_inicio' => $formulario->fecha_inicio,
                'fecha_fin' => $formulario->fecha_fin,
                'created_at' => $formulario->created_at,
                'categoria' => $formulario->categoria ? [
                    'id' => $formulario->categoria->id,
                    'nombre' => $formulario->categoria->nombre,
                    'color' => $formulario->categoria->color,
                ] : null,
                'creador' => $formulario->creador ? [
                    'id' => $formulario->creador->id,
                    'name' => $formulario->creador->name,
                ] : null,
                'estado_temporal' => $formulario->getEstadoTemporal(),
                'estado_temporal_label' => $formulario->getEstadoTemporalLabel(),
                'estado_temporal_color' => $formulario->getEstadoTemporalColor(),
                'estadisticas' => $estadisticas,
                'url_publica' => route('formularios.show', $formulario->slug),
            ];
        });

        // Obtener categorías para filtros
        $categorias = FormularioCategoria::activas()->ordenadas()->get();

        return Inertia::render('Admin/Formularios/Index', [
            'formularios' => $formularios,
            'categorias' => $categorias,
            'filters' => $request->only(['estado', 'activo', 'categoria_id', 'tipo_acceso', 'search', 'advanced_filters']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
        ]);
    }

    /**
     * Aplicar filtros simples para mantener compatibilidad
     */
    protected function applySimpleFilters($query, $request)
    {
        // Solo aplicar si no hay filtros avanzados
        if (!$request->filled('advanced_filters')) {
            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            
            // Filtro por categoría
            if ($request->filled('categoria_id')) {
                $query->where('categoria_id', $request->categoria_id);
            }
            
            // Filtro por tipo de acceso
            if ($request->filled('tipo_acceso')) {
                $query->where('tipo_acceso', $request->tipo_acceso);
            }
            
            // Filtro por activo
            if ($request->filled('activo')) {
                $query->where('activo', $request->activo === 'true' || $request->activo === '1');
            }
        }
    }

    /**
     * Configuración de campos para filtros avanzados
     */
    protected function getFilterFieldsConfig(): array
    {
        $categorias = FormularioCategoria::activas()->ordenadas()->pluck('nombre', 'id');
        
        return [
            [
                'field' => 'titulo',
                'label' => 'Título',
                'type' => 'text',
                'operators' => ['contains', 'equals', 'starts_with', 'ends_with'],
            ],
            [
                'field' => 'descripcion',
                'label' => 'Descripción',
                'type' => 'text',
                'operators' => ['contains', 'equals'],
            ],
            [
                'field' => 'categoria_id',
                'label' => 'Categoría',
                'type' => 'select',
                'operators' => ['equals', 'not_equals'],
                'options' => $categorias->map(function ($nombre, $id) {
                    return ['value' => $id, 'label' => $nombre];
                })->values()->toArray(),
            ],
            [
                'field' => 'tipo_acceso',
                'label' => 'Tipo de Acceso',
                'type' => 'select',
                'operators' => ['equals', 'not_equals'],
                'options' => [
                    ['value' => 'publico', 'label' => 'Público'],
                    ['value' => 'autenticado', 'label' => 'Autenticado'],
                    ['value' => 'con_permiso', 'label' => 'Con Permiso'],
                ],
            ],
            [
                'field' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'operators' => ['equals', 'not_equals'],
                'options' => [
                    ['value' => 'borrador', 'label' => 'Borrador'],
                    ['value' => 'publicado', 'label' => 'Publicado'],
                    ['value' => 'archivado', 'label' => 'Archivado'],
                ],
            ],
            [
                'field' => 'activo',
                'label' => 'Activo',
                'type' => 'boolean',
                'operators' => ['equals'],
            ],
            [
                'field' => 'fecha_inicio',
                'label' => 'Fecha de Inicio',
                'type' => 'date',
                'operators' => ['equals', 'before', 'after', 'between'],
            ],
            [
                'field' => 'fecha_fin',
                'label' => 'Fecha de Fin',
                'type' => 'date',
                'operators' => ['equals', 'before', 'after', 'between'],
            ],
            [
                'field' => 'limite_respuestas',
                'label' => 'Límite de Respuestas',
                'type' => 'number',
                'operators' => ['equals', 'greater_than', 'less_than'],
            ],
            [
                'field' => 'created_at',
                'label' => 'Fecha de Creación',
                'type' => 'date',
                'operators' => ['equals', 'before', 'after', 'between'],
            ],
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $categorias = FormularioCategoria::activas()->ordenadas()->get();
        
        return Inertia::render('Admin/Formularios/Create', [
            'categorias' => $categorias,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'nullable|exists:formulario_categorias,id',
            'configuracion_campos' => 'required|array|min:1',
            'configuracion_campos.*.id' => 'required|string',
            'configuracion_campos.*.type' => 'required|string',
            'configuracion_campos.*.title' => 'required|string',
            'configuracion_campos.*.options' => 'sometimes|array',
            'configuracion_campos.*.options.*.label' => 'sometimes|required_with:configuracion_campos.*.options.*.value|string',
            'configuracion_campos.*.options.*.value' => 'sometimes|nullable|numeric',
            'tipo_acceso' => 'required|in:publico,autenticado,con_permiso',
            'permite_visitantes' => 'boolean',
            'requiere_captcha' => 'boolean',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'limite_respuestas' => 'nullable|integer|min:1',
            'limite_por_usuario' => 'required|integer|min:1',
            'emails_notificacion' => 'nullable|array',
            'emails_notificacion.*' => 'email',
            'mensaje_confirmacion' => 'nullable|string',
            'estado' => 'required|in:borrador,publicado,archivado',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $formulario = Formulario::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'categoria_id' => $request->categoria_id,
                'configuracion_campos' => $request->configuracion_campos,
                'configuracion_general' => $request->configuracion_general ?? [],
                'tipo_acceso' => $request->tipo_acceso,
                'permite_visitantes' => $request->permite_visitantes ?? false,
                'requiere_captcha' => $request->requiere_captcha ?? true,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'limite_respuestas' => $request->limite_respuestas,
                'limite_por_usuario' => $request->limite_por_usuario ?? 1,
                'emails_notificacion' => $request->emails_notificacion,
                'mensaje_confirmacion' => $request->mensaje_confirmacion,
                'estado' => $request->estado,
                'activo' => $request->activo ?? true,
                'creado_por' => Auth::id(),
                'actualizado_por' => Auth::id(),
            ]);

            DB::commit();
            
            return redirect()->route('admin.formularios.index')
                ->with('success', 'Formulario creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error al crear el formulario: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Formulario $formulario): Response
    {
        $formulario->load(['categoria', 'respuestas.usuario']);
        
        // Obtener respuestas paginadas
        $respuestas = FormularioRespuesta::where('formulario_id', $formulario->id)
            ->with('usuario')
            ->where('estado', 'enviado')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        // Obtener todas las respuestas para filtrado (sin paginar)
        $todasLasRespuestas = FormularioRespuesta::where('formulario_id', $formulario->id)
            ->where('estado', 'enviado')
            ->get(['id', 'respuestas'])
            ->map(function ($respuesta) {
                return [
                    'id' => $respuesta->id,
                    'respuestas' => $respuesta->respuestas
                ];
            });
            
        // Transformar respuestas para el frontend
        $respuestas->getCollection()->transform(function ($respuesta) {
            return [
                'id' => $respuesta->id,
                'codigo_confirmacion' => $respuesta->codigo_confirmacion,
                'nombre' => $respuesta->getNombreRespondiente(),
                'email' => $respuesta->getEmailRespondiente(),
                'documento' => $respuesta->getDocumentoRespondiente(),
                'es_visitante' => $respuesta->esDeVisitante(),
                'respuestas' => $respuesta->respuestas,
                'tiempo_llenado' => $respuesta->getTiempoLlenadoFormateado(),
                'created_at' => $respuesta->created_at,
                'enviado_en' => $respuesta->enviado_en,
            ];
        });
        
        // Obtener estadísticas
        $estadisticas = $formulario->getEstadisticas();
        $estadisticasPorCategoria = $formulario->getEstadisticasPorCategoria();
        $estadisticasPorPregunta = $formulario->getEstadisticasPorPregunta();
        $datosRadar = $formulario->getDatosRadarPorCategoria();
        
        return Inertia::render('Admin/Formularios/Show', [
            'formulario' => [
                'id' => $formulario->id,
                'titulo' => $formulario->titulo,
                'descripcion' => $formulario->descripcion,
                'slug' => $formulario->slug,
                'configuracion_campos' => $formulario->configuracion_campos,
                'tipo_acceso' => $formulario->tipo_acceso,
                'estado' => $formulario->estado,
                'activo' => $formulario->activo,
                'categoria' => $formulario->categoria,
                'estadisticas' => $estadisticas,
                'estadisticas_por_categoria' => $estadisticasPorCategoria,
                'estadisticas_por_pregunta' => $estadisticasPorPregunta,
                'datos_radar' => $datosRadar,
                'url_publica' => route('formularios.show', $formulario->slug),
            ],
            'respuestas' => $respuestas,
            'todas_respuestas' => $todasLasRespuestas,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Formulario $formulario): Response
    {
        $categorias = FormularioCategoria::activas()->ordenadas()->get();
        
        return Inertia::render('Admin/Formularios/Edit', [
            'formulario' => $formulario,
            'categorias' => $categorias,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Formulario $formulario)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'nullable|exists:formulario_categorias,id',
            'configuracion_campos' => 'required|array|min:1',
            'configuracion_campos.*.id' => 'required|string',
            'configuracion_campos.*.type' => 'required|string',
            'configuracion_campos.*.title' => 'required|string',
            'configuracion_campos.*.options' => 'sometimes|array',
            'configuracion_campos.*.options.*.label' => 'sometimes|required_with:configuracion_campos.*.options.*.value|string',
            'configuracion_campos.*.options.*.value' => 'sometimes|nullable|numeric',
            'tipo_acceso' => 'required|in:publico,autenticado,con_permiso',
            'permite_visitantes' => 'boolean',
            'requiere_captcha' => 'boolean',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'limite_respuestas' => 'nullable|integer|min:1',
            'limite_por_usuario' => 'required|integer|min:1',
            'emails_notificacion' => 'nullable|array',
            'emails_notificacion.*' => 'email',
            'mensaje_confirmacion' => 'nullable|string',
            'estado' => 'required|in:borrador,publicado,archivado',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $formulario->update([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'categoria_id' => $request->categoria_id,
                'configuracion_campos' => $request->configuracion_campos,
                'configuracion_general' => $request->configuracion_general ?? $formulario->configuracion_general,
                'tipo_acceso' => $request->tipo_acceso,
                'permite_visitantes' => $request->permite_visitantes ?? false,
                'requiere_captcha' => $request->requiere_captcha ?? true,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'limite_respuestas' => $request->limite_respuestas,
                'limite_por_usuario' => $request->limite_por_usuario ?? 1,
                'emails_notificacion' => $request->emails_notificacion,
                'mensaje_confirmacion' => $request->mensaje_confirmacion,
                'estado' => $request->estado,
                'activo' => $request->activo ?? true,
                'actualizado_por' => Auth::id(),
            ]);

            DB::commit();
            
            return redirect()->route('admin.formularios.index')
                ->with('success', 'Formulario actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error al actualizar el formulario: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Formulario $formulario)
    {
        DB::beginTransaction();
        try {
            // Verificar si tiene respuestas
            if ($formulario->respuestas()->exists()) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar un formulario con respuestas. Considere archivarlo en su lugar.');
            }
            
            $formulario->delete();
            
            DB::commit();
            
            return redirect()->route('admin.formularios.index')
                ->with('success', 'Formulario eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error al eliminar el formulario: ' . $e->getMessage());
        }
    }

    /**
     * Exportar respuestas a CSV
     */
    public function exportarRespuestas(Formulario $formulario)
    {
        $respuestas = $formulario->respuestas()
            ->where('estado', 'enviado')
            ->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="respuestas_' . $formulario->slug . '_' . date('Y-m-d') . '.csv"',
        ];
        
        $callback = function() use ($respuestas, $formulario) {
            $file = fopen('php://output', 'w');
            
            // Encabezados
            $headers = [
                'Código Confirmación',
                'Fecha Envío',
                'Nombre',
                'Email',
                'Documento',
                'Tiempo Llenado',
            ];
            
            // Agregar headers de campos del formulario
            foreach ($formulario->configuracion_campos as $campo) {
                $headers[] = $campo['title'];
            }
            
            fputcsv($file, $headers);
            
            // Datos
            foreach ($respuestas as $respuesta) {
                fputcsv($file, $respuesta->exportarACsv());
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Duplicar un formulario existente
     */
    public function duplicate(Formulario $formulario)
    {
        DB::beginTransaction();
        try {
            // Crear título único para la copia
            $nuevoTitulo = $formulario->titulo . ' (Copia)';
            
            // Generar slug único
            $baseSlug = $formulario->slug . '-copia';
            $slug = $baseSlug;
            $counter = 1;
            
            // Verificar si el slug ya existe y generar uno único
            while (Formulario::where('slug', $slug)->where('tenant_id', $formulario->tenant_id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            // Crear el nuevo formulario duplicado
            $nuevoFormulario = Formulario::create([
                'titulo' => $nuevoTitulo,
                'descripcion' => $formulario->descripcion,
                'slug' => $slug,
                'categoria_id' => $formulario->categoria_id,
                'configuracion_campos' => $formulario->configuracion_campos,
                'configuracion_general' => $formulario->configuracion_general,
                'tipo_acceso' => $formulario->tipo_acceso,
                'permite_visitantes' => $formulario->permite_visitantes,
                'requiere_captcha' => $formulario->requiere_captcha,
                'fecha_inicio' => null, // Resetear fechas para que el usuario las configure
                'fecha_fin' => null,
                'limite_respuestas' => $formulario->limite_respuestas,
                'limite_por_usuario' => $formulario->limite_por_usuario,
                'emails_notificacion' => $formulario->emails_notificacion,
                'mensaje_confirmacion' => $formulario->mensaje_confirmacion,
                'estado' => 'borrador', // Siempre crear como borrador
                'activo' => false, // Inactivo por defecto para revisión
                'creado_por' => Auth::id(),
                'actualizado_por' => Auth::id(),
            ]);
            
            // Duplicar permisos si existen
            foreach ($formulario->permisos as $permiso) {
                $nuevoFormulario->permisos()->create([
                    'usuario_id' => $permiso->usuario_id,
                    'role_id' => $permiso->role_id,
                    'tipo_permiso' => $permiso->tipo_permiso,
                    'activo' => $permiso->activo,
                    'valido_desde' => $permiso->valido_desde,
                    'valido_hasta' => $permiso->valido_hasta,
                    'creado_por' => Auth::id(),
                ]);
            }
            
            DB::commit();
            
            // Redirigir a la edición del nuevo formulario
            return redirect()->route('admin.formularios.edit', $nuevoFormulario->id)
                ->with('success', 'Formulario duplicado exitosamente. Ahora puedes editarlo según tus necesidades.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error al duplicar el formulario: ' . $e->getMessage());
        }
    }
}