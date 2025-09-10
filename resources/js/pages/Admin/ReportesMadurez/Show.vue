<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Edit, ClipboardCheck, BarChart3, Building2, MapPin, Home, Briefcase, Calendar, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MatrizMadurez from '@/components/reportes/MatrizMadurez.vue';
import EstadisticasMadurez from '@/components/reportes/EstadisticasMadurez.vue';

interface Categoria {
    id: number;
    nombre: string;
    codigo: string;
    orden: number;
    color: string;
    elementos: Elemento[];
}

interface Elemento {
    id: number;
    categoria_id: number;
    numero: number;
    nombre: string;
    orden: number;
}

interface Evaluacion {
    id: number;
    reporte_id: number;
    elemento_id: number;
    nivel: string;
}

interface ReporteMadurez {
    id: number;
    empresa: string;
    ciudad: string;
    centro_trabajo: string;
    area: string;
    fecha_realizacion: string;
    created_at: string;
    porcentaje_completitud: number;
    creador: {
        id: number;
        name: string;
        email: string;
    };
}

interface Estadisticas {
    por_nivel: Record<string, number>;
    por_categoria: Record<string, {
        nombre: string;
        color: string;
        total_elementos: number;
        elementos_evaluados: number;
        porcentaje: number;
        distribucion_niveles: Record<string, number>;
    }>;
    total_evaluado: number;
    total_elementos: number;
    porcentaje_completitud: number;
}

interface Props {
    reporte: ReporteMadurez;
    categorias: Categoria[];
    evaluacionesExistentes: Record<number, Evaluacion>;
    estadisticas: Estadisticas;
    niveles: Record<string, string>;
}

const props = defineProps<Props>();

// Estados reactivos locales para updates en tiempo real
const evaluacionesLocales = ref({...props.evaluacionesExistentes});
const estadisticasLocales = ref({...props.estadisticas});

// Función para manejar cambios de evaluación en tiempo real
const handleEvaluacionChanged = (elementoId: number, nivel: string | null) => {
    // Actualizar evaluaciones locales
    if (nivel) {
        evaluacionesLocales.value[elementoId] = {
            id: Date.now(),
            elemento_id: elementoId,
            nivel: nivel,
            reporte_id: props.reporte.id
        };
    } else {
        delete evaluacionesLocales.value[elementoId];
    }
    
    // Recalcular estadísticas localmente
    recalcularEstadisticas();
};

// Función para recalcular estadísticas localmente
const recalcularEstadisticas = () => {
    const evaluacionesArray = Object.values(evaluacionesLocales.value);
    const totalEvaluado = evaluacionesArray.length;
    const totalElementos = estadisticasLocales.value.total_elementos;
    
    // Recalcular por nivel global
    const porNivel: Record<string, number> = {
        emergente: 0, resolutivo: 0, laborioso: 0, cooperativo: 0, progresivo: 0
    };
    evaluacionesArray.forEach(evaluacion => {
        if (porNivel[evaluacion.nivel] !== undefined) {
            porNivel[evaluacion.nivel]++;
        }
    });
    
    // Recalcular distribución por niveles por categoría
    const porCategoriaActualizada = {...estadisticasLocales.value.por_categoria};
    
    // Resetear distribuciones
    Object.keys(porCategoriaActualizada).forEach(codigoCategoria => {
        porCategoriaActualizada[codigoCategoria].distribucion_niveles = {
            emergente: 0, resolutivo: 0, laborioso: 0, cooperativo: 0, progresivo: 0
        };
        porCategoriaActualizada[codigoCategoria].elementos_evaluados = 0;
    });
    
    // Recalcular distribuciones basadas en evaluaciones locales y categorías
    props.categorias.forEach(categoria => {
        let elementosEvaluados = 0;
        const distribucionNiveles = {
            emergente: 0, resolutivo: 0, laborioso: 0, cooperativo: 0, progresivo: 0
        };
        
        categoria.elementos.forEach(elemento => {
            const evaluacion = evaluacionesLocales.value[elemento.id];
            if (evaluacion && distribucionNiveles[evaluacion.nivel] !== undefined) {
                distribucionNiveles[evaluacion.nivel]++;
                elementosEvaluados++;
            }
        });
        
        const totalElementosCategoria = categoria.elementos.length;
        const porcentaje = totalElementosCategoria > 0 ? (elementosEvaluados / totalElementosCategoria) * 100 : 0;
        
        if (porCategoriaActualizada[categoria.codigo]) {
            porCategoriaActualizada[categoria.codigo] = {
                ...porCategoriaActualizada[categoria.codigo],
                elementos_evaluados: elementosEvaluados,
                porcentaje: porcentaje,
                distribucion_niveles: distribucionNiveles
            };
        }
    });
    
    // Recalcular porcentaje de completitud
    const porcentajeCompletitud = totalElementos > 0 ? (totalEvaluado / totalElementos) * 100 : 0;
    
    // Actualizar estadísticas locales
    estadisticasLocales.value = {
        ...estadisticasLocales.value,
        por_nivel: porNivel,
        por_categoria: porCategoriaActualizada,
        total_evaluado: totalEvaluado,
        porcentaje_completitud: porcentajeCompletitud
    };
};

// Configurar breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Reportes de Madurez', href: '/admin/reportes-madurez' },
    { title: `${props.reporte.empresa} - ${props.reporte.area}`, href: `/admin/reportes-madurez/${props.reporte.id}` },
];

// Función para formatear fecha
const formatearFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Función para formatear porcentaje
const formatearPorcentaje = (porcentaje: number | undefined | null) => {
    if (porcentaje === undefined || porcentaje === null) return '0%';
    if (porcentaje === 0) return '0%';
    if (porcentaje === 100) return '100%';
    return `${porcentaje.toFixed(1)}%`;
};

// Función para obtener color del badge según completitud
const getCompletitudColor = (porcentaje: number | undefined | null) => {
    if (porcentaje === undefined || porcentaje === null || porcentaje === 0) return 'destructive';
    if (porcentaje < 30) return 'destructive';
    if (porcentaje < 70) return 'default';
    if (porcentaje < 100) return 'secondary';
    return 'default';
};
</script>

<template>
    <Head :title="`Reporte de Madurez - ${reporte.empresa}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.reportes-madurez.index')">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold">Reporte de Madurez</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ reporte.empresa }} - {{ reporte.area }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="getCompletitudColor(estadisticasLocales.porcentaje_completitud)">
                        {{ formatearPorcentaje(estadisticasLocales.porcentaje_completitud) }} completado
                    </Badge>
                    <Link :href="route('admin.reportes-madurez.edit', reporte.id)">
                        <Button variant="outline" size="sm">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Información del reporte -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ClipboardCheck class="h-5 w-5" />
                        Información del Reporte
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="flex items-center gap-2">
                            <Building2 class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium">Empresa</p>
                                <p class="text-sm text-muted-foreground">{{ reporte.empresa }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <MapPin class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium">Ciudad</p>
                                <p class="text-sm text-muted-foreground">{{ reporte.ciudad }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Home class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium">Centro de Trabajo</p>
                                <p class="text-sm text-muted-foreground">{{ reporte.centro_trabajo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Briefcase class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium">Área</p>
                                <p class="text-sm text-muted-foreground">{{ reporte.area }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium">Fecha de Realización</p>
                                <p class="text-sm text-muted-foreground">{{ formatearFecha(reporte.fecha_realizacion) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <User class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium">Creado por</p>
                                <p class="text-sm text-muted-foreground">{{ reporte.creador.name }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabs principales -->
            <Tabs default-value="evaluacion" class="flex-1">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="evaluacion" class="flex items-center gap-2">
                        <ClipboardCheck class="h-4 w-4" />
                        Evaluación
                    </TabsTrigger>
                    <TabsTrigger value="estadisticas" class="flex items-center gap-2">
                        <BarChart3 class="h-4 w-4" />
                        Estadísticas
                    </TabsTrigger>
                </TabsList>

                <!-- Tab de Evaluación -->
                <TabsContent value="evaluacion" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Matriz de Evaluación de Madurez</CardTitle>
                            <CardDescription>
                                Marca el nivel de madurez correspondiente para cada elemento. 
                                Solo se puede seleccionar un nivel por elemento.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <MatrizMadurez 
                                :reporte-id="reporte.id"
                                :categorias="categorias"
                                :evaluaciones-existentes="evaluacionesLocales"
                                :niveles="niveles"
                                @evaluacion-changed="handleEvaluacionChanged"
                            />
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab de Estadísticas -->
                <TabsContent value="estadisticas" class="space-y-4">
                    <EstadisticasMadurez 
                        :estadisticas="estadisticasLocales"
                        :niveles="niveles"
                    />
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>