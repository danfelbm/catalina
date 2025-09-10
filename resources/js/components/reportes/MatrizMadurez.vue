<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { toast } from 'vue-sonner';
import axios from 'axios';
import { ref, computed, reactive } from 'vue';

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

interface Props {
    reporteId: number;
    categorias: Categoria[];
    evaluacionesExistentes: Record<number, Evaluacion>;
    niveles: Record<string, string>;
}

const props = defineProps<Props>();

// Guardado en progreso
const guardandoElemento = ref<number | null>(null);

// Estado local reactivo de evaluaciones para updates en tiempo real
const evaluacionesLocales = reactive({...props.evaluacionesExistentes});

// Emits para notificar cambios al componente padre
const emit = defineEmits<{
    evaluacionChanged: [elementoId: number, nivel: string | null]
}>();

// Niveles de madurez en orden
const nivelesOrdenados = ['emergente', 'resolutivo', 'laborioso', 'cooperativo', 'progresivo'];

// Función para obtener el color de fondo de la categoría con opacidad
const getCategoryBackgroundColor = (color: string) => {
    const hex = color.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    return `rgba(${r}, ${g}, ${b}, 0.15)`;
};

// Función para obtener el color del texto de la categoría
const getCategoryTextColor = (color: string) => {
    // Como usamos rgba con opacidad baja (0.15), siempre usar texto oscuro para mejor legibilidad
    return '#374151'; // Siempre texto oscuro para mejor contraste
};

// Función para verificar si un elemento está evaluado en un nivel específico
const isElementoEvaluado = (elementoId: number, nivel: string): boolean => {
    const evaluacion = evaluacionesLocales[elementoId];
    return evaluacion?.nivel === nivel;
};

// Función para manejar el cambio de evaluación
const handleEvaluacionChange = async (elementoId: number, nivel: string, checked: boolean) => {
    guardandoElemento.value = elementoId;
    
    try {
        if (checked) {
            // Actualizar estado local inmediatamente (optimistic update)
            evaluacionesLocales[elementoId] = { 
                id: Date.now(), // ID temporal
                elemento_id: elementoId,
                nivel: nivel,
                reporte_id: props.reporteId
            };
            
            // Guardar nueva evaluación en el servidor
            const response = await axios.post(`/admin/reportes-madurez/${props.reporteId}/evaluacion`, {
                elemento_id: elementoId,
                nivel: nivel,
            });
            
            if (response.data.success) {
                toast.success('Evaluación guardada correctamente');
                emit('evaluacionChanged', elementoId, nivel);
            }
        } else {
            // Actualizar estado local inmediatamente
            delete evaluacionesLocales[elementoId];
            
            // Eliminar evaluación existente del servidor
            const response = await axios.post(`/admin/reportes-madurez/${props.reporteId}/remove-evaluacion`, {
                elemento_id: elementoId,
            });
            
            if (response.data.success) {
                toast.success('Evaluación eliminada correctamente');
                emit('evaluacionChanged', elementoId, null);
            }
        }
    } catch (error) {
        console.error('Error al guardar evaluación:', error);
        toast.error('Error al guardar la evaluación');
        
        // Revertir cambios locales en caso de error
        if (checked) {
            delete evaluacionesLocales[elementoId];
        } else {
            // Restaurar evaluación anterior si estaba
            const evaluacionAnterior = props.evaluacionesExistentes[elementoId];
            if (evaluacionAnterior) {
                evaluacionesLocales[elementoId] = evaluacionAnterior;
            }
        }
    } finally {
        guardandoElemento.value = null;
    }
};

// Función para obtener el color del header del nivel
const getNivelHeaderColor = (nivel: string) => {
    const colors = {
        emergente: '#dc2626', // Rojo
        resolutivo: '#ea580c', // Naranja
        laborioso: '#ca8a04', // Amarillo
        cooperativo: '#2563eb', // Azul
        progresivo: '#16a34a', // Verde
    };
    return colors[nivel] || '#6b7280';
};

// Función para obtener el color de fondo del header del nivel
const getNivelHeaderBgColor = (nivel: string) => {
    const colors = {
        emergente: '#fef2f2', // Rojo claro
        resolutivo: '#fff7ed', // Naranja claro
        laborioso: '#fefce8', // Amarillo claro
        cooperativo: '#eff6ff', // Azul claro
        progresivo: '#f0fdf4', // Verde claro
    };
    return colors[nivel] || '#f9fafb';
};
</script>

<template>
    <div class="w-full overflow-x-auto">
        <div class="min-w-[800px]">
            <!-- Tabla de matriz de madurez -->
            <Table class="border-collapse border border-gray-300">
                <!-- Header de la tabla -->
                <TableHeader>
                    <TableRow class="bg-gray-50">
                        <TableHead class="border border-gray-300 w-8 text-center font-bold bg-gray-100">
                            No.
                        </TableHead>
                        <TableHead class="border border-gray-300 min-w-[300px] font-bold bg-gray-100">
                            ELEMENTO
                        </TableHead>
                        <TableHead 
                            v-for="nivel in nivelesOrdenados" 
                            :key="nivel"
                            class="border border-gray-300 w-24 text-center font-bold text-xs p-2 rotate-0"
                            :style="{
                                backgroundColor: getNivelHeaderBgColor(nivel),
                                color: getNivelHeaderColor(nivel)
                            }"
                        >
                            {{ props.niveles[nivel] || nivel.toUpperCase() }}
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <!-- Iterar por categorías -->
                    <template v-for="categoria in categorias" :key="categoria.id">
                        <!-- Header de categoría -->
                        <TableRow>
                            <TableCell 
                                :colspan="7"
                                class="border border-gray-300 p-3 font-bold text-center text-sm"
                                :style="{
                                    backgroundColor: getCategoryBackgroundColor(categoria.color),
                                    color: getCategoryTextColor(categoria.color),
                                    borderLeft: `4px solid ${categoria.color}`
                                }"
                            >
                                {{ categoria.nombre }}
                            </TableCell>
                        </TableRow>

                        <!-- Elementos de la categoría -->
                        <TableRow 
                            v-for="elemento in categoria.elementos" 
                            :key="elemento.id"
                            class="hover:bg-gray-50"
                        >
                            <!-- Número del elemento -->
                            <TableCell class="border border-gray-300 text-center font-medium bg-gray-50">
                                {{ elemento.numero }}
                            </TableCell>

                            <!-- Nombre del elemento -->
                            <TableCell class="border border-gray-300 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm">{{ elemento.nombre }}</span>
                                    <div 
                                        v-if="guardandoElemento === elemento.id"
                                        class="ml-2 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                    />
                                </div>
                            </TableCell>

                            <!-- Checkboxes para cada nivel -->
                            <TableCell 
                                v-for="nivel in nivelesOrdenados" 
                                :key="`${elemento.id}-${nivel}`"
                                class="border border-gray-300 text-center p-2"
                            >
                                <div class="flex justify-center">
                                    <Checkbox
                                        :id="`elemento-${elemento.id}-${nivel}`"
                                        :checked="isElementoEvaluado(elemento.id, nivel)"
                                        :disabled="guardandoElemento === elemento.id"
                                        @update:checked="(checked) => handleEvaluacionChange(elemento.id, nivel, checked)"
                                        class="h-5 w-5"
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <!-- Leyenda de niveles -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-sm font-medium mb-3">Leyenda de Niveles de Madurez:</h4>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div 
                    v-for="nivel in nivelesOrdenados" 
                    :key="nivel"
                    class="flex items-center gap-2 p-2 rounded text-xs"
                    :style="{
                        backgroundColor: getNivelHeaderBgColor(nivel),
                        color: getNivelHeaderColor(nivel)
                    }"
                >
                    <div 
                        class="w-3 h-3 rounded-full"
                        :style="{ backgroundColor: getNivelHeaderColor(nivel) }"
                    ></div>
                    <span class="font-medium">{{ props.niveles[nivel] || nivel.toUpperCase() }}</span>
                </div>
            </div>
        </div>

        <!-- Instrucciones -->
        <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h4 class="text-sm font-medium text-blue-900 mb-2">Instrucciones:</h4>
            <ul class="text-xs text-blue-800 space-y-1">
                <li>• Selecciona un solo nivel de madurez por cada elemento</li>
                <li>• Al marcar un checkbox, se desmarcará automáticamente cualquier nivel previamente seleccionado</li>
                <li>• Los cambios se guardan automáticamente</li>
                <li>• Puedes desmarcar un checkbox para eliminar la evaluación de ese elemento</li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
/* Asegurar que la tabla mantenga el layout exacto como en la imagen */
.table-fixed {
    table-layout: fixed;
}

/* Estilo personalizado para los checkboxes */
:deep(.checkbox) {
    border-radius: 2px;
}

/* Evitar que el texto se rompa en celdas pequeñas */
.text-nowrap {
    white-space: nowrap;
}
</style>