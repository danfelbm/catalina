<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
// DatePicker reemplazado por input HTML5 type="date"
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Building2, MapPin, Home, Briefcase, Calendar } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface ReporteMadurez {
    id?: number;
    empresa: string;
    ciudad: string;
    centro_trabajo: string;
    area: string;
    fecha_realizacion: string;
}

interface Props {
    reporte: ReporteMadurez | null;
}

const props = defineProps<Props>();

// Determinar si estamos creando o editando
const isEditing = computed(() => props.reporte !== null);

// Configurar breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Reportes de Madurez', href: '/admin/reportes-madurez' },
    { 
        title: isEditing.value ? 'Editar Reporte' : 'Nuevo Reporte', 
        href: isEditing.value 
            ? `/admin/reportes-madurez/${props.reporte?.id}/edit` 
            : '/admin/reportes-madurez/create' 
    },
];

// Función auxiliar para formatear fecha a YYYY-MM-DD
// Esta función convierte fechas ISO a formato requerido por input type="date"
const formatearFechaParaInput = (fecha: string | null | undefined): string => {
    if (!fecha) return '';
    
    // Si la fecha ya está en formato YYYY-MM-DD, devolverla tal cual
    if (/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
        return fecha;
    }
    
    // Si es una fecha ISO o con timestamp, extraer solo YYYY-MM-DD
    // Formato esperado: "2024-01-15T00:00:00.000000Z" o similar
    // También maneja fechas como "2024-01-15 00:00:00"
    const partesFecha = fecha.split(/[T\s]/)[0]; // Divide por T o espacio
    
    // Validar que la parte extraída tiene formato de fecha válido
    if (partesFecha && /^\d{4}-\d{2}-\d{2}$/.test(partesFecha)) {
        return partesFecha;
    }
    
    return '';
};

// Configurar formulario con valores iniciales
const form = useForm({
    empresa: props.reporte?.empresa || '',
    ciudad: props.reporte?.ciudad || '',
    centro_trabajo: props.reporte?.centro_trabajo || '',
    area: props.reporte?.area || '',
    fecha_realizacion: formatearFechaParaInput(props.reporte?.fecha_realizacion),
});

// Función para enviar formulario
const enviarFormulario = () => {
    if (isEditing.value && props.reporte) {
        form.put(route('admin.reportes-madurez.update', props.reporte.id), {
            onSuccess: () => {
                // Redirección se maneja automáticamente por el controlador
            },
        });
    } else {
        form.post(route('admin.reportes-madurez.store'), {
            onSuccess: () => {
                // Redirección se maneja automáticamente por el controlador
            },
        });
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Editar Reporte de Madurez' : 'Nuevo Reporte de Madurez'" />

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
                        <h1 class="text-2xl font-semibold">
                            {{ isEditing ? 'Editar Reporte de Madurez' : 'Nuevo Reporte de Madurez' }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ isEditing 
                                ? 'Modifica los datos básicos del reporte'
                                : 'Completa los datos básicos para crear un nuevo reporte' 
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <Card>
                <CardHeader>
                    <CardTitle>Información del Reporte</CardTitle>
                    <CardDescription>
                        Los datos básicos del reporte pueden editarse en cualquier momento. 
                        La evaluación de madurez se realiza en la vista de detalle del reporte.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="enviarFormulario" class="space-y-6">
                        <!-- Empresa -->
                        <div class="space-y-2">
                            <Label for="empresa" class="flex items-center gap-2">
                                <Building2 class="h-4 w-4" />
                                Empresa
                            </Label>
                            <Input
                                id="empresa"
                                v-model="form.empresa"
                                type="text"
                                placeholder="Nombre de la empresa"
                                :class="{ 'border-destructive': form.errors.empresa }"
                                required
                            />
                            <p v-if="form.errors.empresa" class="text-sm text-destructive">
                                {{ form.errors.empresa }}
                            </p>
                        </div>

                        <!-- Ciudad -->
                        <div class="space-y-2">
                            <Label for="ciudad" class="flex items-center gap-2">
                                <MapPin class="h-4 w-4" />
                                Ciudad
                            </Label>
                            <Input
                                id="ciudad"
                                v-model="form.ciudad"
                                type="text"
                                placeholder="Ciudad donde se realiza el reporte"
                                :class="{ 'border-destructive': form.errors.ciudad }"
                                required
                            />
                            <p v-if="form.errors.ciudad" class="text-sm text-destructive">
                                {{ form.errors.ciudad }}
                            </p>
                        </div>

                        <!-- Centro de Trabajo -->
                        <div class="space-y-2">
                            <Label for="centro_trabajo" class="flex items-center gap-2">
                                <Home class="h-4 w-4" />
                                Centro de Trabajo
                            </Label>
                            <Input
                                id="centro_trabajo"
                                v-model="form.centro_trabajo"
                                type="text"
                                placeholder="Centro o lugar de trabajo específico"
                                :class="{ 'border-destructive': form.errors.centro_trabajo }"
                                required
                            />
                            <p v-if="form.errors.centro_trabajo" class="text-sm text-destructive">
                                {{ form.errors.centro_trabajo }}
                            </p>
                        </div>

                        <!-- Área -->
                        <div class="space-y-2">
                            <Label for="area" class="flex items-center gap-2">
                                <Briefcase class="h-4 w-4" />
                                Área
                            </Label>
                            <Input
                                id="area"
                                v-model="form.area"
                                type="text"
                                placeholder="Área o departamento evaluado"
                                :class="{ 'border-destructive': form.errors.area }"
                                required
                            />
                            <p v-if="form.errors.area" class="text-sm text-destructive">
                                {{ form.errors.area }}
                            </p>
                        </div>

                        <!-- Fecha de Realización -->
                        <div class="space-y-2">
                            <Label class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                Fecha de Realización
                            </Label>
                            <Input
                                type="date"
                                v-model="form.fecha_realizacion"
                                :class="{ 'border-destructive': form.errors.fecha_realizacion }"
                            />
                            <p v-if="form.errors.fecha_realizacion" class="text-sm text-destructive">
                                {{ form.errors.fecha_realizacion }}
                            </p>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex items-center justify-end gap-4 pt-6">
                            <Link :href="route('admin.reportes-madurez.index')">
                                <Button variant="outline" type="button">
                                    Cancelar
                                </Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ isEditing ? 'Actualizar' : 'Crear' }} Reporte
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Información adicional para reportes existentes -->
            <Card v-if="isEditing && reporte">
                <CardHeader>
                    <CardTitle>Próximos Pasos</CardTitle>
                    <CardDescription>
                        Una vez guardados los cambios, puedes proceder a evaluar la madurez.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-4">
                        <Link :href="route('admin.reportes-madurez.show', reporte.id)">
                            <Button variant="secondary">
                                Ir a Evaluación de Madurez
                            </Button>
                        </Link>
                        <p class="text-sm text-muted-foreground">
                            Accede a la matriz de evaluación y estadísticas del reporte
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>