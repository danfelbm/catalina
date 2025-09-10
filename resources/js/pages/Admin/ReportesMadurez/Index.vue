<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Eye, Trash2, ClipboardCheck, Building2, MapPin, Calendar } from 'lucide-vue-next';
import { ref } from 'vue';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';

interface ReporteMadurez {
    id: number;
    empresa: string;
    ciudad: string;
    centro_trabajo: string;
    area: string;
    fecha_realizacion: string;
    created_at: string;
    evaluaciones_count: number;
    porcentaje_completitud: number;
    creador: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    reportes: {
        data: ReporteMadurez[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        advanced_filters?: string;
    };
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Reportes de Madurez', href: '/admin/reportes-madurez' },
];

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por empresa, ciudad, centro o área...',
    quickSearchFields: ['empresa', 'ciudad', 'centro_trabajo', 'area'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

// Reporte a eliminar
const reporteAEliminar = ref<ReporteMadurez | null>(null);

// Función para formatear fecha
const formatearFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Función para formatear porcentaje de completitud
const formatearPorcentaje = (porcentaje: number) => {
    if (porcentaje === 0) return '0%';
    if (porcentaje === 100) return '100%';
    return `${porcentaje.toFixed(1)}%`;
};

// Función para obtener color del badge según completitud
const getCompletitudColor = (porcentaje: number) => {
    if (porcentaje === 0) return 'destructive';
    if (porcentaje < 30) return 'destructive';
    if (porcentaje < 70) return 'default';
    if (porcentaje < 100) return 'secondary';
    return 'default';
};

// Función para eliminar reporte
const eliminarReporte = () => {
    if (!reporteAEliminar.value) return;
    
    router.delete(`/admin/reportes-madurez/${reporteAEliminar.value.id}`, {
        onSuccess: () => {
            reporteAEliminar.value = null;
        },
    });
};
</script>

<template>
    <Head title="Reportes de Madurez" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <ClipboardCheck class="h-6 w-6 text-primary" />
                    <h1 class="text-2xl font-semibold">Reportes de Madurez</h1>
                </div>
                <Link :href="route('admin.reportes-madurez.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Reporte
                    </Button>
                </Link>
            </div>

            <!-- Filtros avanzados -->
            <Card>
                <CardContent class="pt-6">
                    <AdvancedFilters :config="filterConfig" />
                </CardContent>
            </Card>

            <!-- Tabla de reportes -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Empresa</TableHead>
                                <TableHead>Ciudad</TableHead>
                                <TableHead>Centro de Trabajo</TableHead>
                                <TableHead>Área</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Completitud</TableHead>
                                <TableHead>Creado por</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="reporte in reportes.data" :key="reporte.id">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <Building2 class="h-4 w-4 text-muted-foreground" />
                                        {{ reporte.empresa }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <MapPin class="h-4 w-4 text-muted-foreground" />
                                        {{ reporte.ciudad }}
                                    </div>
                                </TableCell>
                                <TableCell>{{ reporte.centro_trabajo }}</TableCell>
                                <TableCell>{{ reporte.area }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4 text-muted-foreground" />
                                        {{ formatearFecha(reporte.fecha_realizacion) }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getCompletitudColor(reporte.porcentaje_completitud)">
                                        {{ formatearPorcentaje(reporte.porcentaje_completitud) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ reporte.creador.name }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.reportes-madurez.show', reporte.id)">
                                            <Button variant="ghost" size="sm">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="route('admin.reportes-madurez.edit', reporte.id)">
                                            <Button variant="ghost" size="sm">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="reporteAEliminar = reporte"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>
                                                        ¿Estás seguro?
                                                    </AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Esta acción no se puede deshacer. Se eliminará permanentemente
                                                        el reporte de madurez y todas sus evaluaciones.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel @click="reporteAEliminar = null">
                                                        Cancelar
                                                    </AlertDialogCancel>
                                                    <AlertDialogAction @click="eliminarReporte()">
                                                        Eliminar
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="reportes.links" class="flex items-center justify-center space-x-2">
                <template v-for="(link, index) in reportes.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
                        :class="{ 'bg-primary text-primary-foreground': link.active }"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3 opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>

            <!-- Estado vacío -->
            <div v-if="reportes.data.length === 0" class="text-center py-8">
                <ClipboardCheck class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay reportes de madurez</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Comienza creando tu primer reporte de madurez.
                </p>
                <div class="mt-6">
                    <Link :href="route('admin.reportes-madurez.create')">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo Reporte
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>