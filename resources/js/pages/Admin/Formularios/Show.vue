<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    ArrowLeft, Edit, Download, ExternalLink, Users, FileText, 
    Calendar, Clock, BarChart3, Copy, Eye, Trash2, Filter, X
} from 'lucide-vue-next';
import RadarChart from '@/components/RadarChart.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'vue-sonner';

// Props
interface Props {
    formulario: {
        id: number;
        titulo: string;
        descripcion?: string;
        slug: string;
        configuracion_campos: any[];
        tipo_acceso: string;
        estado: string;
        activo: boolean;
        categoria?: {
            id: number;
            nombre: string;
        };
        estadisticas: {
            total_respuestas: number;
            respuestas_hoy: number;
            respuestas_semana: number;
            respuestas_mes: number;
            usuarios_unicos: number;
            visitantes_unicos: number;
        };
        estadisticas_por_categoria?: {
            [key: string]: {
                id: string;
                nombre: string;
                descripcion?: string;
                promedio: number;
                total_respuestas: number;
                total_preguntas: number;
                distribucion: any;
            };
        };
        estadisticas_por_pregunta?: Array<{
            campo_id: string;
            pregunta: string;
            dimension: string;
            promedio: number;
            total_respuestas: number;
            suma_total: number;
            distribucion: any;
            valores_min_max: {
                min: number;
                max: number;
            };
        }>;
        datos_radar?: {
            labels: string[];
            datasets: any[];
            options?: any;
        };
        url_publica: string;
    };
    respuestas: {
        data: Array<{
            id: number;
            codigo_confirmacion: string;
            nombre: string;
            email?: string;
            documento?: string;
            es_visitante: boolean;
            respuestas: Record<string, any>;
            tiempo_llenado?: string;
            created_at: string;
            enviado_en: string;
        }>;
        links: any;
        meta: any;
    };
    todas_respuestas: Array<{
        id: number;
        respuestas: Record<string, any>;
    }>;
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Formularios', href: '/admin/formularios' },
    { title: props.formulario.titulo, href: '#' },
];

// Estado local
const activeTab = ref('resumen');
const selectedRespuesta = ref<any>(null);
const showRespuestaModal = ref(false);

// Estado de filtros
const filtros = ref({
    genero: 'all',
    cargo_directivo: 'all',
    edad_min: 18,
    edad_max: 56
});

// Métodos
const handleEdit = () => {
    router.get(route('admin.formularios.edit', props.formulario.id));
};

const handleExport = () => {
    window.location.href = route('admin.formularios.exportar', props.formulario.id);
};

const copyUrlToClipboard = () => {
    navigator.clipboard.writeText(props.formulario.url_publica).then(() => {
        toast.success('URL copiada al portapapeles');
    });
};

const viewRespuesta = (respuesta: any) => {
    selectedRespuesta.value = respuesta;
    showRespuestaModal.value = true;
};

const formatDate = (date: string) => {
    return format(new Date(date), "d 'de' MMMM 'de' yyyy, HH:mm", { locale: es });
};

const getTipoAccesoLabel = (tipo: string) => {
    const labels: Record<string, string> = {
        publico: 'Público',
        autenticado: 'Autenticado',
        con_permiso: 'Con Permiso',
    };
    return labels[tipo] || tipo;
};

// Función para normalizar género
const normalizarGenero = (genero: string): string => {
    const normalizado = genero.toLowerCase();
    if (normalizado === 'femenino' || normalizado === 'mujer') return 'Femenino';
    if (normalizado === 'masculino' || normalizado === 'hombre') return 'Masculino';
    return genero;
};

// Función para aplicar filtros a las respuestas
const aplicarFiltros = (respuestas: any[]): any[] => {
    return respuestas.filter(respuesta => {
        const data = respuesta.respuestas;
        
        // Filtro por género
        if (filtros.value.genero && filtros.value.genero !== 'all' && normalizarGenero(data.genero) !== filtros.value.genero) {
            return false;
        }
        
        // Filtro por cargo directivo
        if (filtros.value.cargo_directivo && filtros.value.cargo_directivo !== 'all' && data.cargo_directivo !== filtros.value.cargo_directivo) {
            return false;
        }
        
        // Filtro por edad
        const edad = parseInt(data.edad);
        if (edad < filtros.value.edad_min || edad > filtros.value.edad_max) {
            return false;
        }
        
        return true;
    });
};

// Función para calcular estadísticas por categoría con filtros
const calcularEstadisticasPorCategoriaFiltradas = (respuestasFiltradas: any[]) => {
    const campos = props.formulario.configuracion_campos;
    if (!respuestasFiltradas.length || !campos) return {};
    
    // Agrupar campos por categoría
    const camposPorCategoria: Record<string, any> = {};
    campos.forEach(campo => {
        if (campo.category?.name) {
            const categoriaId = campo.category.id || 'sin_categoria';
            if (!camposPorCategoria[categoriaId]) {
                camposPorCategoria[categoriaId] = {
                    nombre: campo.category.name,
                    descripcion: campo.category.description || null,
                    campos: []
                };
            }
            camposPorCategoria[categoriaId].campos.push(campo);
        }
    });
    
    const estadisticas: Record<string, any> = {};
    
    Object.entries(camposPorCategoria).forEach(([categoriaId, categoriaInfo]) => {
        const valoresCategoria: number[] = [];
        
        categoriaInfo.campos.forEach((campo: any) => {
            respuestasFiltradas.forEach(respuesta => {
                const valor = respuesta.respuestas[campo.id];
                if (valor !== null && !isNaN(Number(valor))) {
                    valoresCategoria.push(Number(valor));
                }
            });
        });
        
        if (valoresCategoria.length > 0) {
            const suma = valoresCategoria.reduce((acc, val) => acc + val, 0);
            const promedio = suma / valoresCategoria.length;
            
            estadisticas[categoriaId] = {
                id: categoriaId,
                nombre: categoriaInfo.nombre,
                descripcion: categoriaInfo.descripcion,
                promedio: Math.round(promedio * 100) / 100,
                total_respuestas: valoresCategoria.length,
                total_preguntas: categoriaInfo.campos.length,
            };
        }
    });
    
    return estadisticas;
};

// Función para calcular estadísticas por pregunta con filtros
const calcularEstadisticasPorPreguntaFiltradas = (respuestasFiltradas: any[]) => {
    const campos = props.formulario.configuracion_campos;
    if (!respuestasFiltradas.length || !campos) return [];
    
    const estadisticas: any[] = [];
    
    campos.forEach(campo => {
        if (!['rating', 'scale', 'select', 'radio'].includes(campo.type) && !campo.options) {
            return;
        }
        
        const valores: number[] = [];
        respuestasFiltradas.forEach(respuesta => {
            const valor = respuesta.respuestas[campo.id];
            if (valor !== null && !isNaN(Number(valor))) {
                valores.push(Number(valor));
            }
        });
        
        if (valores.length > 0) {
            const suma = valores.reduce((acc, val) => acc + val, 0);
            const promedio = suma / valores.length;
            
            estadisticas.push({
                campo_id: campo.id,
                pregunta: campo.title || 'Sin título',
                dimension: campo.category?.name || '',
                promedio: Math.round(promedio * 100) / 100,
                total_respuestas: valores.length,
            });
        }
    });
    
    // Ordenar por dimensión y luego por campo_id
    estadisticas.sort((a, b) => {
        if (a.dimension === b.dimension) {
            return a.campo_id.localeCompare(b.campo_id);
        }
        return a.dimension.localeCompare(b.dimension);
    });
    
    return estadisticas;
};

// Función para limpiar filtros
const limpiarFiltros = () => {
    filtros.value = {
        genero: 'all',
        cargo_directivo: 'all',
        edad_min: 18,
        edad_max: 56
    };
};

const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        borrador: 'secondary',
        publicado: 'default',
        archivado: 'outline',
    };
    return colors[estado] || 'secondary';
};

// Computed
const porcentajeVisitantes = computed(() => {
    const total = props.formulario.estadisticas.usuarios_unicos + props.formulario.estadisticas.visitantes_unicos;
    if (total === 0) return 0;
    return Math.round((props.formulario.estadisticas.visitantes_unicos / total) * 100);
});

const tieneEstadisticasPorCategoria = computed(() => {
    return props.formulario.estadisticas_por_categoria && 
           Object.keys(props.formulario.estadisticas_por_categoria).length > 0;
});

const tieneEstadisticasPorPregunta = computed(() => {
    return props.formulario.estadisticas_por_pregunta && 
           props.formulario.estadisticas_por_pregunta.length > 0;
});

// Computed properties para estadísticas filtradas
const respuestasFiltradas = computed(() => {
    if (!props.todas_respuestas || props.todas_respuestas.length === 0) return [];
    
    // Si no hay filtros aplicados, devolver todas las respuestas
    const hayFiltros = (filtros.value.genero && filtros.value.genero !== 'all') || 
                      (filtros.value.cargo_directivo && filtros.value.cargo_directivo !== 'all') || 
                      filtros.value.edad_min > 18 || 
                      filtros.value.edad_max < 56;
    
    if (!hayFiltros) {
        return props.todas_respuestas;
    }
    
    return aplicarFiltros(props.todas_respuestas);
});

const estadisticasPorCategoriaFiltradas = computed(() => {
    // Si no hay filtros, usar datos originales
    const hayFiltros = (filtros.value.genero && filtros.value.genero !== 'all') || 
                      (filtros.value.cargo_directivo && filtros.value.cargo_directivo !== 'all') || 
                      filtros.value.edad_min > 18 || 
                      filtros.value.edad_max < 56;
    
    if (!hayFiltros) {
        return props.formulario.estadisticas_por_categoria || {};
    }
    
    return calcularEstadisticasPorCategoriaFiltradas(respuestasFiltradas.value);
});

const estadisticasPorPreguntaFiltradas = computed(() => {
    // Si no hay filtros, usar datos originales
    const hayFiltros = (filtros.value.genero && filtros.value.genero !== 'all') || 
                      (filtros.value.cargo_directivo && filtros.value.cargo_directivo !== 'all') || 
                      filtros.value.edad_min > 18 || 
                      filtros.value.edad_max < 56;
    
    if (!hayFiltros) {
        return props.formulario.estadisticas_por_pregunta || [];
    }
    
    return calcularEstadisticasPorPreguntaFiltradas(respuestasFiltradas.value);
});

const datosRadarFiltrados = computed(() => {
    const estadisticas = estadisticasPorCategoriaFiltradas.value;
    
    const labels: string[] = [];
    const promedios: number[] = [];
    
    Object.values(estadisticas).forEach((categoria: any) => {
        labels.push(categoria.nombre);
        promedios.push(categoria.promedio);
    });
    
    return {
        labels,
        datasets: [
            {
                label: 'Promedio por Dimensión',
                data: promedios,
                borderColor: 'rgb(59, 130, 246)', // blue-500
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderWidth: 2,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointRadius: 4,
            }
        ]
    };
});

// Lista de géneros únicos para el dropdown
const generosDisponibles = computed(() => {
    if (!props.todas_respuestas) return [];
    
    const generos = new Set<string>();
    props.todas_respuestas.forEach(respuesta => {
        const genero = respuesta.respuestas.genero;
        if (genero) {
            generos.add(normalizarGenero(genero));
        }
    });
    
    return Array.from(generos).sort();
});

const datosRadarFormateados = computed(() => {
    if (!props.formulario.datos_radar) return null;
    
    return {
        labels: props.formulario.datos_radar.labels,
        datasets: props.formulario.datos_radar.datasets
    };
});
</script>

<template>
    <Head :title="formulario.titulo" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Encabezado -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.formularios.index')">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft class="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold">{{ formulario.titulo }}</h1>
                            <p v-if="formulario.descripcion" class="text-muted-foreground">
                                {{ formulario.descripcion }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="handleEdit">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                        <Button 
                            v-if="formulario.estadisticas.total_respuestas > 0"
                            @click="handleExport"
                        >
                            <Download class="mr-2 h-4 w-4" />
                            Exportar CSV
                        </Button>
                    </div>
                </div>
                
                <!-- Badges de estado -->
                <div class="flex items-center gap-2">
                    <Badge :variant="getEstadoColor(formulario.estado)">
                        {{ formulario.estado }}
                    </Badge>
                    <Badge variant="outline">
                        {{ getTipoAccesoLabel(formulario.tipo_acceso) }}
                    </Badge>
                    <Badge v-if="formulario.categoria" variant="secondary">
                        {{ formulario.categoria.nombre }}
                    </Badge>
                    <Badge :variant="formulario.activo ? 'default' : 'destructive'">
                        {{ formulario.activo ? 'Activo' : 'Inactivo' }}
                    </Badge>
                </div>
            </div>
            
            <!-- URL Pública -->
            <Alert class="mb-6">
                <AlertDescription class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <ExternalLink class="h-4 w-4" />
                        <span class="font-medium">URL Pública:</span>
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm">
                            {{ formulario.url_publica }}
                        </code>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="ghost" size="sm" @click="copyUrlToClipboard">
                            <Copy class="h-4 w-4" />
                        </Button>
                        <a :href="formulario.url_publica" target="_blank">
                            <Button variant="ghost" size="sm">
                                <ExternalLink class="h-4 w-4" />
                            </Button>
                        </a>
                    </div>
                </AlertDescription>
            </Alert>
            
            <!-- Tabs -->
            <Tabs v-model="activeTab" class="space-y-6">
                <TabsList>
                    <TabsTrigger value="resumen">Resumen</TabsTrigger>
                    <TabsTrigger value="respuestas">
                        Respuestas ({{ formulario.estadisticas.total_respuestas }})
                    </TabsTrigger>
                    <TabsTrigger value="estadisticas">Estadísticas</TabsTrigger>
                </TabsList>
                
                <!-- Tab: Resumen -->
                <TabsContent value="resumen" class="space-y-6">
                    <!-- Estadísticas rápidas -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Total Respuestas
                                </CardTitle>
                                <FileText class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ formulario.estadisticas.total_respuestas }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ formulario.estadisticas.respuestas_hoy }} hoy
                                </p>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Usuarios Únicos
                                </CardTitle>
                                <Users class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ formulario.estadisticas.usuarios_unicos }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Usuarios registrados
                                </p>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Visitantes
                                </CardTitle>
                                <Users class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ formulario.estadisticas.visitantes_unicos }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ porcentajeVisitantes }}% del total
                                </p>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Esta Semana
                                </CardTitle>
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ formulario.estadisticas.respuestas_semana }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Respuestas
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <!-- Información del formulario -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración del Formulario</CardTitle>
                            <CardDescription>
                                Estructura y campos del formulario
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-medium mb-2">Campos del formulario:</h4>
                                    <div class="space-y-2">
                                        <div 
                                            v-for="(campo, index) in formulario.configuracion_campos" 
                                            :key="campo.id"
                                            class="flex items-center gap-2 p-2 bg-gray-50 rounded"
                                        >
                                            <span class="text-sm font-medium">{{ index + 1 }}.</span>
                                            <span class="text-sm">{{ campo.title }}</span>
                                            <Badge variant="outline" class="text-xs">
                                                {{ campo.type }}
                                            </Badge>
                                            <Badge v-if="campo.required" variant="destructive" class="text-xs">
                                                Requerido
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
                
                <!-- Tab: Respuestas -->
                <TabsContent value="respuestas" class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Respuestas Recibidas</CardTitle>
                            <CardDescription>
                                Lista de todas las respuestas enviadas
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="respuestas.data.length === 0" class="text-center py-8">
                                <FileText class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <p class="text-muted-foreground">
                                    No hay respuestas aún
                                </p>
                            </div>
                            
                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Código</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>Tiempo</TableHead>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="respuesta in respuestas.data" :key="respuesta.id">
                                        <TableCell class="font-mono text-xs">
                                            {{ respuesta.codigo_confirmacion }}
                                        </TableCell>
                                        <TableCell class="whitespace-normal break-words">{{ respuesta.nombre }}</TableCell>
                                        <TableCell class="whitespace-normal break-words">{{ respuesta.email || '-' }}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {{ respuesta.es_visitante ? 'Visitante' : 'Usuario' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ respuesta.tiempo_llenado || '-' }}</TableCell>
                                        <TableCell class="text-sm">
                                            {{ formatDate(respuesta.enviado_en) }}
                                        </TableCell>
                                        <TableCell>
                                            <Button 
                                                variant="ghost" 
                                                size="sm"
                                                @click="viewRespuesta(respuesta)"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            
                            <!-- Paginación -->
                            <div v-if="respuestas.links && respuestas.links.length > 3" class="mt-4">
                                <nav class="flex items-center justify-center gap-1">
                                    <template v-for="link in respuestas.links" :key="link.label">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            :class="[
                                                'px-3 py-2 text-sm rounded-md',
                                                link.active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-white hover:bg-gray-50 border'
                                            ]"
                                            v-html="link.label"
                                        />
                                        <span
                                            v-else
                                            class="px-3 py-2 text-sm text-gray-400"
                                            v-html="link.label"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
                
                <!-- Tab: Estadísticas -->
                <TabsContent value="estadisticas" class="space-y-6">
                    <!-- Panel de Filtros Flotante -->
                    <div class="sticky top-6 z-40 mb-6">
                        <div class="backdrop-blur-lg bg-white/60 dark:bg-gray-900/80 border border-gray-200/50 dark:border-gray-700/50 rounded-xl sm:rounded-2xl shadow-lg p-3 sm:p-4">
                            <!-- Fila única con título, filtros, resultados y limpiar -->
                            <div class="flex gap-4 items-end">
                                <!-- Título (izquierda) -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <div class="p-1.5 bg-primary/10 rounded-md">
                                        <Filter class="h-3.5 w-3.5 text-primary" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-xs text-foreground">Filtros</h3>
                                    </div>
                                </div>
                                
                                <!-- Filtros (centro) -->
                                <div class="flex gap-2 items-end flex-1">
                                    <!-- Filtro por Género -->
                                <div class="space-y-1 flex-1">
                                    <Label for="genero" class="text-xs font-medium">Género</Label>
                                    <Select v-model="filtros.genero">
                                        <SelectTrigger id="genero" class="h-8 text-xs w-full">
                                            <SelectValue placeholder="Todos" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all" class="text-xs">Todos los géneros</SelectItem>
                                            <SelectItem 
                                                v-for="genero in generosDisponibles" 
                                                :key="genero" 
                                                :value="genero"
                                                class="text-xs"
                                            >
                                                {{ genero }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Filtro por Cargo Directivo -->
                                <div class="space-y-1 flex-1">
                                    <Label for="cargo" class="text-xs font-medium">Cargo Directivo</Label>
                                    <Select v-model="filtros.cargo_directivo">
                                        <SelectTrigger id="cargo" class="h-8 text-xs w-full">
                                            <SelectValue placeholder="Todos" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all" class="text-xs">Todos los cargos</SelectItem>
                                            <SelectItem value="Si" class="text-xs">Sí</SelectItem>
                                            <SelectItem value="No" class="text-xs">No</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Filtro por Edad Mínima -->
                                <div class="space-y-1 w-20">
                                    <Label for="edad-min" class="text-xs font-medium">Edad Mín.</Label>
                                    <Input 
                                        id="edad-min"
                                        type="number" 
                                        v-model.number="filtros.edad_min"
                                        :min="18"
                                        :max="56"
                                        placeholder="18"
                                        class="h-8 text-xs w-full"
                                    />
                                </div>

                                <!-- Filtro por Edad Máxima -->
                                <div class="space-y-1 w-20">
                                    <Label for="edad-max" class="text-xs font-medium">Edad Máx.</Label>
                                    <Input 
                                        id="edad-max"
                                        type="number" 
                                        v-model.number="filtros.edad_max"
                                        :min="18"
                                        :max="56"
                                        placeholder="56"
                                        class="h-8 text-xs w-full"
                                    />
                                </div>
                                </div>
                                
                                <!-- Resultados y Limpiar (derecha) -->
                                <div class="flex items-center gap-3 shrink-0">
                                    <!-- Información de resultados -->
                                    <div class="bg-gray-100 dark:bg-gray-800/50 rounded-md px-2.5 py-1.5 border border-gray-200/60 dark:border-gray-700/60">
                                        <div class="flex items-center gap-2">
                                            <div class="text-xs font-medium text-foreground">
                                                {{ respuestasFiltradas.length }} de {{ props.todas_respuestas?.length || 0 }}
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <div 
                                                    v-if="(filtros.genero && filtros.genero !== 'all') || (filtros.cargo_directivo && filtros.cargo_directivo !== 'all') || filtros.edad_min > 18 || filtros.edad_max < 56"
                                                    class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"
                                                ></div>
                                                <span class="text-xs text-muted-foreground">
                                                    {{ ((filtros.genero && filtros.genero !== 'all') || (filtros.cargo_directivo && filtros.cargo_directivo !== 'all') || filtros.edad_min > 18 || filtros.edad_max < 56) ? 'Filtrado' : 'Sin filtros' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Botón limpiar -->
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        @click="limpiarFiltros"
                                        class="flex items-center gap-1.5 text-xs h-8"
                                    >
                                        <X class="h-3 w-3" />
                                        Limpiar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Gráfico Radar - Solo si hay datos por categorías -->
                    <Card v-if="Object.keys(estadisticasPorCategoriaFiltradas).length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BarChart3 class="h-5 w-5" />
                                Análisis por Dimensiones
                            </CardTitle>
                            <CardDescription>
                                Promedio de respuestas por categoría/dimensión
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <RadarChart 
                                :data="datosRadarFiltrados"
                                title=""
                                :height="400"
                                :min-value="1"
                                :max-value="4"
                            />
                        </CardContent>
                    </Card>

                    <!-- Tabla de Estadísticas por Categoría -->
                    <Card v-if="Object.keys(estadisticasPorCategoriaFiltradas).length > 0">
                        <CardHeader>
                            <CardTitle>Estadísticas Detalladas por Dimensión</CardTitle>
                            <CardDescription>
                                Análisis numérico de cada categoría/dimensión
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-6 lg:grid-cols-3">
                                <!-- Tabla principal -->
                                <div class="lg:col-span-2">
                                    <Table class="table-fixed w-full overflow-hidden">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead class="w-1/3">Dimensión</TableHead>
                                                <TableHead class="text-center w-24">Promedio</TableHead>
                                                <TableHead class="text-center w-24">Preguntas</TableHead>
                                                <TableHead class="w-2/5">Descripción</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow 
                                                v-for="(stats, categoriaId) in estadisticasPorCategoriaFiltradas" 
                                                :key="categoriaId"
                                            >
                                                <TableCell class="font-medium w-1/3 whitespace-normal">
                                                    <div class="max-w-full break-words leading-tight">
                                                        {{ stats.nombre }}
                                                    </div>
                                                </TableCell>
                                                <TableCell class="text-center">
                                                    <Badge 
                                                        :class="[
                                                            'font-mono text-white',
                                                            stats.promedio > 3.3 ? 'bg-green-600' : 
                                                            stats.promedio >= 3 ? 'bg-blue-500' : 
                                                            stats.promedio >= 2.7 ? 'bg-orange-500' : 
                                                            'bg-red-600'
                                                        ]"
                                                        variant="outline"
                                                    >
                                                        {{ stats.promedio }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell class="text-center">{{ stats.total_preguntas }}</TableCell>
                                                <TableCell class="text-sm text-muted-foreground w-2/5 whitespace-normal">
                                                    <div class="max-w-full break-words leading-relaxed">
                                                        {{ stats.descripcion || '-' }}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>

                                <!-- Tabla de interpretación -->
                                <div class="lg:col-span-1">
                                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                        <h4 class="font-semibold text-sm mb-3 text-center">INTERPRETACIÓN DE RESULTADOS</h4>
                                        <div class="space-y-0 text-xs border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
                                            <!-- Header -->
                                            <div class="grid grid-cols-2 bg-teal-600 text-white">
                                                <div class="px-2 py-1.5 font-semibold text-center border-r border-white/30">MEDIA</div>
                                                <div class="px-2 py-1.5 font-semibold text-center">Interpretación</div>
                                            </div>
                                            <!-- Filas -->
                                            <div class="grid grid-cols-2 bg-green-600 text-white">
                                                <div class="px-2 py-1.5 text-center font-medium border-r border-white/30">> 3.3</div>
                                                <div class="px-2 py-1.5 text-center text-xs">Buen Nivel. Permite mantener y mejorar continuamente</div>
                                            </div>
                                            <div class="grid grid-cols-2 bg-blue-500 text-white">
                                                <div class="px-2 py-1.5 text-center font-medium border-r border-white/30">3 a 3.3</div>
                                                <div class="px-2 py-1.5 text-center text-xs">Buen nivel con ligera necesidad de mejora</div>
                                            </div>
                                            <div class="grid grid-cols-2 bg-orange-500 text-white">
                                                <div class="px-2 py-1.5 text-center font-medium border-r border-white/30">2.7 a 2.99</div>
                                                <div class="px-2 py-1.5 text-center text-xs">Nivel bajo, con necesidad de mejora</div>
                                            </div>
                                            <div class="grid grid-cols-2 bg-red-600 text-white">
                                                <div class="px-2 py-1.5 text-center font-medium border-r border-white/30">< 2.7</div>
                                                <div class="px-2 py-1.5 text-center text-xs">Nivel muy bajo con gran necesidad de mejora</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tabla de Estadísticas por Pregunta Individual -->
                    <Card v-if="estadisticasPorPreguntaFiltradas.length > 0">
                        <CardHeader>
                            <CardTitle>Estadísticas por Pregunta Individual</CardTitle>
                            <CardDescription>
                                Promedio y detalles por cada pregunta del formulario
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Tabla de interpretación arriba -->
                            <div class="mb-6 flex justify-center">
                                <div class="inline-block bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                    <h4 class="font-semibold text-sm mb-3 text-center">INTERPRETACIÓN DE RESULTADOS</h4>
                                    <div class="text-xs border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
                                        <!-- Header -->
                                        <div class="grid grid-cols-2 bg-teal-600 text-white">
                                            <div class="px-3 py-2 font-semibold text-center border-r border-white/30">MEDIA</div>
                                            <div class="px-3 py-2 font-semibold text-center">Interpretación</div>
                                        </div>
                                        <!-- Filas -->
                                        <div class="grid grid-cols-2 bg-green-600 text-white">
                                            <div class="px-3 py-2 text-center font-medium border-r border-white/30">> 3.3</div>
                                            <div class="px-3 py-2 text-center">Buen Nivel. Permite mantener y mejorar continuamente</div>
                                        </div>
                                        <div class="grid grid-cols-2 bg-blue-500 text-white">
                                            <div class="px-3 py-2 text-center font-medium border-r border-white/30">3 a 3.3</div>
                                            <div class="px-3 py-2 text-center">Buen nivel con ligera necesidad de mejora</div>
                                        </div>
                                        <div class="grid grid-cols-2 bg-orange-500 text-white">
                                            <div class="px-3 py-2 text-center font-medium border-r border-white/30">2.7 a 2.99</div>
                                            <div class="px-3 py-2 text-center">Nivel bajo, con necesidad de mejora</div>
                                        </div>
                                        <div class="grid grid-cols-2 bg-red-600 text-white">
                                            <div class="px-3 py-2 text-center font-medium border-r border-white/30">< 2.7</div>
                                            <div class="px-3 py-2 text-center">Nivel muy bajo con gran necesidad de mejora</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla principal -->
                            <div class="overflow-x-auto">
                                <Table class="min-w-full">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead class="w-1/4">Dimensión</TableHead>
                                            <TableHead class="w-2/3">Pregunta</TableHead>
                                            <TableHead class="text-center w-1/12">Promedio</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow 
                                            v-for="stats in estadisticasPorPreguntaFiltradas" 
                                            :key="stats.campo_id"
                                        >
                                            <TableCell class="font-medium whitespace-normal">
                                                <div class="text-xs text-muted-foreground break-words">
                                                    {{ stats.dimension || 'Sin dimensión' }}
                                                </div>
                                            </TableCell>
                                            <TableCell class="whitespace-normal">
                                                <div class="text-sm leading-tight break-words">
                                                    {{ stats.pregunta }}
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <Badge 
                                                    :class="[
                                                        'font-mono text-sm text-white',
                                                        stats.promedio > 3.3 ? 'bg-green-600' : 
                                                        stats.promedio >= 3 ? 'bg-blue-500' : 
                                                        stats.promedio >= 2.7 ? 'bg-orange-500' : 
                                                        'bg-red-600'
                                                    ]"
                                                    variant="outline"
                                                >
                                                    {{ stats.promedio }}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>

                </TabsContent>
            </Tabs>
        </div>
        
        <!-- Modal de Detalle de Respuesta -->
        <Dialog v-model:open="showRespuestaModal">
            <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Detalle de Respuesta
                    </DialogTitle>
                    <DialogDescription>
                        Código: {{ selectedRespuesta?.codigo_confirmacion }}
                    </DialogDescription>
                </DialogHeader>
                
                <div v-if="selectedRespuesta" class="space-y-6">
                    <!-- Información del Usuario -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Información del Usuario</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Nombre</label>
                                    <p class="font-medium">{{ selectedRespuesta.nombre }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Email</label>
                                    <p>{{ selectedRespuesta.email || 'No proporcionado' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Documento</label>
                                    <p>{{ selectedRespuesta.documento || 'No proporcionado' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Tipo de Usuario</label>
                                    <Badge variant="outline">
                                        {{ selectedRespuesta.es_visitante ? 'Visitante' : 'Usuario Registrado' }}
                                    </Badge>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Tiempo de Llenado</label>
                                    <p>{{ selectedRespuesta.tiempo_llenado || 'No medido' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-muted-foreground">Fecha de Envío</label>
                                    <p>{{ formatDate(selectedRespuesta.enviado_en) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <!-- Respuestas del Formulario -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Respuestas del Formulario</CardTitle>
                            <CardDescription>
                                Detalle de todas las respuestas proporcionadas
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-6">
                                <div 
                                    v-for="(campo, index) in formulario.configuracion_campos" 
                                    :key="campo.id"
                                    class="border-b pb-4 last:border-b-0"
                                >
                                    <div class="mb-2">
                                        <label class="text-sm font-medium text-muted-foreground">
                                            {{ index + 1 }}. {{ campo.title }}
                                            <Badge v-if="campo.required" variant="destructive" class="ml-1 text-xs">
                                                Requerido
                                            </Badge>
                                            <Badge variant="outline" class="ml-1 text-xs">
                                                {{ campo.type }}
                                            </Badge>
                                        </label>
                                        <p v-if="campo.description" class="text-sm text-muted-foreground mt-1">
                                            {{ campo.description }}
                                        </p>
                                    </div>
                                    
                                    <!-- Mostrar la respuesta según el tipo de campo -->
                                    <div class="mt-3">
                                        <!-- Respuesta de texto -->
                                        <div 
                                            v-if="['text', 'textarea', 'email', 'number'].includes(campo.type)"
                                            class="p-3 bg-gray-50 rounded-md"
                                        >
                                            <p class="whitespace-pre-wrap">
                                                {{ selectedRespuesta.respuestas[campo.id] || 'Sin respuesta' }}
                                            </p>
                                        </div>
                                        
                                        <!-- Respuesta de selección -->
                                        <div 
                                            v-else-if="['select', 'radio'].includes(campo.type)"
                                            class="p-3 bg-gray-50 rounded-md"
                                        >
                                            <Badge variant="secondary">
                                                {{ selectedRespuesta.respuestas[campo.id] || 'Sin selección' }}
                                            </Badge>
                                        </div>
                                        
                                        <!-- Respuesta de múltiple selección -->
                                        <div 
                                            v-else-if="campo.type === 'checkbox'"
                                            class="p-3 bg-gray-50 rounded-md"
                                        >
                                            <div class="flex flex-wrap gap-2">
                                                <Badge 
                                                    v-if="Array.isArray(selectedRespuesta.respuestas[campo.id]) && selectedRespuesta.respuestas[campo.id].length > 0"
                                                    v-for="opcion in selectedRespuesta.respuestas[campo.id]" 
                                                    :key="opcion"
                                                    variant="secondary"
                                                >
                                                    {{ opcion }}
                                                </Badge>
                                                <span v-else class="text-muted-foreground">Sin selecciones</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Respuesta de escala/rating -->
                                        <div 
                                            v-else-if="['rating', 'scale'].includes(campo.type)"
                                            class="p-3 bg-gray-50 rounded-md"
                                        >
                                            <div class="flex items-center gap-2">
                                                <Badge 
                                                    :class="[
                                                        'font-mono text-white',
                                                        selectedRespuesta.respuestas[campo.id] >= 4 ? 'bg-green-600' : 
                                                        selectedRespuesta.respuestas[campo.id] >= 3 ? 'bg-blue-500' : 
                                                        selectedRespuesta.respuestas[campo.id] >= 2 ? 'bg-orange-500' : 
                                                        'bg-red-600'
                                                    ]"
                                                    variant="outline"
                                                >
                                                    {{ selectedRespuesta.respuestas[campo.id] || 'Sin puntuación' }}
                                                </Badge>
                                                <span class="text-sm text-muted-foreground">
                                                    / {{ campo.max_value || campo.scale_max || 5 }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Otros tipos de respuesta -->
                                        <div 
                                            v-else
                                            class="p-3 bg-gray-50 rounded-md"
                                        >
                                            <p class="text-sm">
                                                {{ JSON.stringify(selectedRespuesta.respuestas[campo.id]) || 'Sin respuesta' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mensaje si no hay respuestas -->
                                <div 
                                    v-if="!formulario.configuracion_campos || formulario.configuracion_campos.length === 0"
                                    class="text-center py-8"
                                >
                                    <FileText class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                    <p class="text-muted-foreground">No hay campos configurados en este formulario</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>