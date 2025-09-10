<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReporteMadurez;
use App\Models\ReporteMadurezCategoria;
use App\Models\ReporteMadurezElemento;
use App\Models\ReporteMadurezEvaluacion;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReporteMadurezController extends Controller
{
    use HasAdvancedFilters;

    /**
     * Mostrar listado de reportes de madurez
     */
    public function index(Request $request): Response
    {
        $query = ReporteMadurez::with(['creador'])
            ->withCount('evaluaciones');

        // Campos permitidos para filtros avanzados
        $allowedFields = [
            'empresa', 'ciudad', 'centro_trabajo', 'area',
            'fecha_realizacion', 'created_at', 'updated_at'
        ];

        // Campos para búsqueda rápida
        $quickSearchFields = ['empresa', 'ciudad', 'centro_trabajo', 'area'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);

        // Ordenamiento
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginación
        $reportes = $query->paginate(15)->withQueryString();

        // Agregar porcentaje de completitud a cada reporte
        $reportes->getCollection()->transform(function ($reporte) {
            $reporte->porcentaje_completitud = $reporte->porcentaje_completitud;
            return $reporte;
        });

        return Inertia::render('Admin/ReportesMadurez/Index', [
            'reportes' => $reportes,
            'filters' => $request->only(['search', 'advanced_filters']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
        ]);
    }

    /**
     * Mostrar formulario para crear nuevo reporte
     */
    public function create(): Response
    {
        return Inertia::render('Admin/ReportesMadurez/Form', [
            'reporte' => null,
        ]);
    }

    /**
     * Guardar nuevo reporte
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'empresa' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'centro_trabajo' => 'required|string|max:255',
            'area' => 'required|string|max:255',
            'fecha_realizacion' => 'required|date',
        ]);

        $validated['created_by'] = Auth::id();

        ReporteMadurez::create($validated);

        return redirect()->route('admin.reportes-madurez.index')
            ->with('success', 'Reporte de madurez creado exitosamente.');
    }

    /**
     * Mostrar reporte específico (con tabs para evaluación y estadísticas)
     */
    public function show(ReporteMadurez $reportes_madurez): Response
    {
        $reportes_madurez->load(['evaluaciones.elemento.categoria', 'creador']);

        // Obtener todas las categorías con sus elementos
        $categorias = ReporteMadurezCategoria::with(['elementos' => function($query) {
            $query->ordenado();
        }])->ordenado()->get();

        // Obtener evaluaciones existentes indexadas por elemento_id
        $evaluacionesExistentes = $reportes_madurez->evaluaciones()
            ->get()
            ->keyBy('elemento_id');

        // Obtener estadísticas
        $estadisticas = $reportes_madurez->getEstadisticas();

        return Inertia::render('Admin/ReportesMadurez/Show', [
            'reporte' => $reportes_madurez,
            'categorias' => $categorias,
            'evaluacionesExistentes' => $evaluacionesExistentes,
            'estadisticas' => $estadisticas,
            'niveles' => ReporteMadurezEvaluacion::NIVELES,
        ]);
    }

    /**
     * Mostrar formulario para editar reporte
     */
    public function edit(ReporteMadurez $reportes_madurez): Response
    {
        return Inertia::render('Admin/ReportesMadurez/Form', [
            'reporte' => $reportes_madurez,
        ]);
    }

    /**
     * Actualizar reporte
     */
    public function update(Request $request, ReporteMadurez $reportes_madurez): RedirectResponse
    {
        $validated = $request->validate([
            'empresa' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'centro_trabajo' => 'required|string|max:255',
            'area' => 'required|string|max:255',
            'fecha_realizacion' => 'required|date',
        ]);

        $reportes_madurez->update($validated);

        return redirect()->route('admin.reportes-madurez.index')
            ->with('success', 'Reporte de madurez actualizado exitosamente.');
    }

    /**
     * Eliminar reporte
     */
    public function destroy(ReporteMadurez $reportes_madurez): RedirectResponse
    {
        $reportes_madurez->delete();

        return redirect()->route('admin.reportes-madurez.index')
            ->with('success', 'Reporte de madurez eliminado exitosamente.');
    }

    /**
     * Guardar o actualizar evaluación de un elemento
     */
    public function saveEvaluacion(Request $request, ReporteMadurez $reportes_madurez)
    {
        $validated = $request->validate([
            'elemento_id' => 'required|exists:reporte_madurez_elementos,id',
            'nivel' => [
                'required',
                Rule::in(array_keys(ReporteMadurezEvaluacion::NIVELES))
            ],
        ]);

        try {
            // Eliminar evaluación existente si la hay (solo puede haber una por elemento)
            ReporteMadurezEvaluacion::where('reporte_id', $reportes_madurez->id)
                ->where('elemento_id', $validated['elemento_id'])
                ->delete();

            // Crear nueva evaluación
            ReporteMadurezEvaluacion::create([
                'reporte_id' => $reportes_madurez->id,
                'elemento_id' => $validated['elemento_id'],
                'nivel' => $validated['nivel'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evaluación guardada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la evaluación'
            ], 500);
        }
    }

    /**
     * Eliminar evaluación de un elemento
     */
    public function removeEvaluacion(Request $request, ReporteMadurez $reportes_madurez)
    {
        $validated = $request->validate([
            'elemento_id' => 'required|exists:reporte_madurez_elementos,id',
        ]);

        try {
            ReporteMadurezEvaluacion::where('reporte_id', $reportes_madurez->id)
                ->where('elemento_id', $validated['elemento_id'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Evaluación eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la evaluación'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas actualizadas del reporte
     */
    public function getEstadisticas(ReporteMadurez $reportes_madurez)
    {
        try {
            $estadisticas = $reportes_madurez->getEstadisticas();

            return response()->json([
                'success' => true,
                'estadisticas' => $estadisticas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas'
            ], 500);
        }
    }

    /**
     * Configuración de campos para filtros avanzados
     */
    protected function getFilterFieldsConfig(): array
    {
        return [
            [
                'key' => 'empresa',
                'label' => 'Empresa',
                'type' => 'text',
                'operators' => ['contains', 'equals', 'starts_with', 'ends_with']
            ],
            [
                'key' => 'ciudad',
                'label' => 'Ciudad',
                'type' => 'text',
                'operators' => ['contains', 'equals', 'starts_with', 'ends_with']
            ],
            [
                'key' => 'centro_trabajo',
                'label' => 'Centro de Trabajo',
                'type' => 'text',
                'operators' => ['contains', 'equals', 'starts_with', 'ends_with']
            ],
            [
                'key' => 'area',
                'label' => 'Área',
                'type' => 'text',
                'operators' => ['contains', 'equals', 'starts_with', 'ends_with']
            ],
            [
                'key' => 'fecha_realizacion',
                'label' => 'Fecha de Realización',
                'type' => 'date',
                'operators' => ['equals', 'greater_than', 'less_than', 'between']
            ],
            [
                'key' => 'created_at',
                'label' => 'Fecha de Creación',
                'type' => 'datetime',
                'operators' => ['equals', 'greater_than', 'less_than', 'between']
            ],
        ];
    }
}
