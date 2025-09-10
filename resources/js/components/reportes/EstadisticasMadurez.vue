<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';
import { Chart, registerables } from 'chart.js';
import { Trophy, Calculator } from 'lucide-vue-next';

// Registrar todos los componentes de Chart.js
Chart.register(...registerables);

interface Estadisticas {
    por_nivel: Record<string, number>;
    por_categoria: Record<string, {
        nombre: string;
        color: string;
        total_elementos: number;
        elementos_evaluados: number;
        porcentaje: number;
    }>;
    total_evaluado: number;
    total_elementos: number;
    porcentaje_completitud: number;
}

interface Props {
    estadisticas: Estadisticas;
    niveles: Record<string, string>;
}

const props = defineProps<Props>();

// Computed: Nivel con más respuestas (nivel dominante)
const nivelDominante = computed(() => {
    const niveles = props.estadisticas.por_nivel;
    let maxNivel = '';
    let maxCantidad = 0;
    
    for (const [nivel, cantidad] of Object.entries(niveles)) {
        if (cantidad > maxCantidad) {
            maxCantidad = cantidad;
            maxNivel = nivel;
        }
    }
    
    return props.niveles[maxNivel] || maxNivel.toUpperCase();
});

// Computed: Puntaje promedio de cultura SST
const puntajePromedio = computed(() => {
    const niveles = props.estadisticas.por_nivel;
    
    // Multiplicadores por nivel
    const multiplicadores = {
        emergente: 1,
        resolutivo: 2,
        laborioso: 3,
        cooperativo: 4,
        progresivo: 5
    };
    
    // Calcular suma ponderada
    let sumaPonderada = 0;
    for (const [nivel, cantidad] of Object.entries(niveles)) {
        if (multiplicadores[nivel]) {
            sumaPonderada += cantidad * multiplicadores[nivel];
        }
    }
    
    // Dividir entre 24 (total de elementos)
    const promedio = sumaPonderada / 24;
    return promedio.toFixed(2);
});

// Referencias a los canvas de los gráficos
const chartNivelesRef = ref<HTMLCanvasElement | null>(null);
const chartCategoriasRef = ref<HTMLCanvasElement | null>(null);

// Referencias a las instancias de los gráficos
let chartNiveles: Chart | null = null;
let chartCategorias: Chart | null = null;

// Colores para los niveles
const coloresNiveles = {
    emergente: '#dc2626',
    resolutivo: '#ea580c',
    laborioso: '#ca8a04',
    cooperativo: '#2563eb',
    progresivo: '#16a34a',
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

// Función para crear el gráfico de niveles
const crearGraficoNiveles = () => {
    if (!chartNivelesRef.value) return;

    const data = Object.entries(props.estadisticas.por_nivel);
    const labels = data.map(([nivel]) => props.niveles[nivel] || nivel);
    const valores = data.map(([, valor]) => valor);
    const colores = data.map(([nivel]) => coloresNiveles[nivel] || '#6b7280');

    chartNiveles = new Chart(chartNivelesRef.value, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: valores,
                backgroundColor: colores,
                borderColor: colores,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = valores.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
                            return `${label}: ${value} elementos (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
};

// Función para crear el gráfico de categorías
const crearGraficoCategorias = () => {
    if (!chartCategoriasRef.value) return;

    const data = Object.entries(props.estadisticas.por_categoria);
    const labels = data.map(([, categoria]) => categoria.nombre);
    
    // Colores para cada nivel de madurez
    const coloresNiveles = {
        emergente: '#dc2626',
        resolutivo: '#ea580c', 
        laborioso: '#ca8a04',
        cooperativo: '#2563eb',
        progresivo: '#16a34a'
    };

    // Crear datasets para cada nivel
    const datasets = Object.keys(coloresNiveles).map(nivel => ({
        label: props.niveles[nivel] || nivel.toUpperCase(),
        data: data.map(([, categoria]) => categoria.distribucion_niveles?.[nivel] || 0),
        backgroundColor: coloresNiveles[nivel],
        borderColor: coloresNiveles[nivel],
        borderWidth: 1,
    }));

    chartCategorias = new Chart(chartCategoriasRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: (value) => Math.floor(value) === value ? value : ''
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: (context) => {
                            return `Categoría: ${context[0].label}`;
                        },
                        label: (context) => {
                            const valor = context.parsed.y;
                            return `${context.dataset.label}: ${valor} elemento${valor !== 1 ? 's' : ''}`;
                        }
                    }
                }
            }
        }
    });
};

// Función para limpiar gráficos
const limpiarGraficos = () => {
    if (chartNiveles) {
        chartNiveles.destroy();
        chartNiveles = null;
    }
    if (chartCategorias) {
        chartCategorias.destroy();
        chartCategorias = null;
    }
};

// Función para actualizar gráficos
const actualizarGraficos = () => {
    limpiarGraficos();
    setTimeout(() => {
        crearGraficoNiveles();
        crearGraficoCategorias();
    }, 100);
};

// Crear gráficos al montar el componente
onMounted(() => {
    actualizarGraficos();
});

// Recrear gráficos cuando cambien las estadísticas
watch(() => props.estadisticas, () => {
    actualizarGraficos();
}, { deep: true });

// Limpiar al desmontar
onUnmounted(() => {
    limpiarGraficos();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Resumen rápido -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-sm font-medium">Total Evaluado</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ estadisticas.total_evaluado }}</div>
                    <p class="text-xs text-muted-foreground">
                        de {{ estadisticas.total_elementos }} elementos
                    </p>
                </CardContent>
            </Card>
            
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-sm font-medium">Completitud General</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatearPorcentaje(estadisticas.porcentaje_completitud) }}</div>
                    <p class="text-xs text-muted-foreground">del reporte</p>
                </CardContent>
            </Card>
            
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-sm font-medium">Categorías</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ Object.keys(estadisticas.por_categoria).length }}</div>
                    <p class="text-xs text-muted-foreground">evaluadas</p>
                </CardContent>
            </Card>
            
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-sm font-medium">Progreso</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                        <div 
                            class="bg-primary h-2 rounded-full transition-all duration-300"
                            :style="{ width: `${estadisticas.porcentaje_completitud}%` }"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ estadisticas.total_evaluado }} / {{ estadisticas.total_elementos }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Gráfico de distribución por niveles -->
            <Card>
                <CardHeader>
                    <CardTitle>Distribución por Nivel de Madurez</CardTitle>
                    <CardDescription>
                        Cantidad de elementos evaluados en cada nivel
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="h-64 flex items-center justify-center">
                        <canvas ref="chartNivelesRef" class="max-h-full max-w-full"></canvas>
                    </div>
                </CardContent>
            </Card>

            <!-- Gráfico de distribución de niveles por categorías -->
            <Card>
                <CardHeader>
                    <CardTitle>Distribución de Niveles por Categoría</CardTitle>
                    <CardDescription>
                        Cantidad de elementos en cada nivel de madurez por categoría
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="h-80 flex items-center justify-center">
                        <canvas ref="chartCategoriasRef" class="max-h-full max-w-full"></canvas>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Sección "Detalles por Categoría" removida por solicitud del usuario -->

        <!-- Distribución detallada por niveles -->
        <Card>
            <CardHeader>
                <CardTitle>Total de respuestas por nivel</CardTitle>
                <CardDescription>
                    Cantidad exacta de elementos en cada nivel de madurez
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div 
                        v-for="(valor, nivel) in estadisticas.por_nivel" 
                        :key="nivel"
                        class="p-4 rounded-lg border text-center"
                        :style="{ 
                            backgroundColor: `${coloresNiveles[nivel] || '#6b7280'}15`,
                            borderColor: coloresNiveles[nivel] || '#6b7280'
                        }"
                    >
                        <div 
                            class="w-8 h-8 rounded-full mx-auto mb-2"
                            :style="{ backgroundColor: coloresNiveles[nivel] || '#6b7280' }"
                        />
                        <h4 class="font-medium text-sm mb-1">{{ niveles[nivel] || nivel }}</h4>
                        <div class="text-2xl font-bold" :style="{ color: coloresNiveles[nivel] || '#6b7280' }">
                            {{ valor }}
                        </div>
                        <p class="text-xs text-muted-foreground">elementos</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Sección Sumario -->
        <Card>
            <CardHeader>
                <CardTitle>Sumario</CardTitle>
                <CardDescription>
                    Resumen de la evaluación de madurez
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nivel de cultura -->
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg bg-primary/10">
                            <Trophy class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Nivel de cultura</p>
                            <p class="text-xl font-semibold">{{ nivelDominante }}</p>
                        </div>
                    </div>
                    
                    <!-- Puntaje promedio de cultura SST -->
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg bg-primary/10">
                            <Calculator class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Puntaje promedio de cultura SST</p>
                            <p class="text-xl font-semibold">{{ puntajePromedio }}</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>