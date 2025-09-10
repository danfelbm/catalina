<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2, Eye, X, GitBranch } from 'lucide-vue-next';
import { watch, onMounted, ref, computed, nextTick } from 'vue';
import { useFormBuilder } from '@/composables/useFormBuilder';
import GeographicRestrictions from '@/components/forms/GeographicRestrictions.vue';
import RepeaterBuilder from './RepeaterBuilder.vue';
import ConditionalFieldConfig from './ConditionalFieldConfig.vue';
import type { FormField, GeographicRestrictions as GeographicRestrictionsType } from '@/types/forms';
import { FIELD_TYPES } from '@/types/forms';

interface Props {
    modelValue: FormField[];
    disabled?: boolean;
    title?: string;
    description?: string;
    showEditableOption?: boolean; // Mostrar opción "Campo editable" (solo para candidaturas)
    // Props para perfil_candidatura en votaciones (deprecated)
    showPerfilCandidaturaConfig?: boolean; // Mostrar configuración avanzada para perfil_candidatura
    cargos?: Array<{ id: number; nombre: string; ruta_jerarquica?: string }>;
    periodosElectorales?: Array<{ id: number; nombre: string; fecha_inicio: string; fecha_fin: string }>;
    // Props para campo convocatoria en votaciones
    showConvocatoriaConfig?: boolean; // Mostrar configuración para campo convocatoria
    convocatorias?: Array<{ 
        id: number; 
        nombre: string; 
        cargo?: { nombre: string; ruta_jerarquica?: string };
        periodo_electoral?: { nombre: string };
        estado_temporal?: string;
    }>;
    context?: 'convocatoria' | 'votacion' | 'candidatura'; // Contexto del formulario
}

interface Emits {
    (e: 'update:modelValue', value: FormField[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    title: 'Constructor de Formulario',
    description: 'Agrega los campos que aparecerán en el formulario',
    showEditableOption: false,
    showPerfilCandidaturaConfig: false,
    cargos: () => [],
    periodosElectorales: () => [],
    showConvocatoriaConfig: false,
    convocatorias: () => [],
    context: 'convocatoria',
});

const emit = defineEmits<Emits>();

const {
    fields,
    newField,
    showFieldForm,
    editingFieldIndex,
    addField,
    editField,
    removeField,
    resetFieldForm,
    addOption,
    removeOption,
    setFields,
    canSave,
    ensureCategoryObject,
} = useFormBuilder(props.modelValue);

// Watch para inicializar configuraciones cuando se cambia el tipo de campo
watch(() => newField.type, (newType) => {
    // Siempre asegurar que conditionalConfig existe
    if (!newField.conditionalConfig) {
        newField.conditionalConfig = {
            enabled: false,
            showWhen: 'all',
            conditions: [],
        };
    }
    
    if (newType === 'perfil_candidatura' && !newField.perfilCandidaturaConfig) {
        newField.perfilCandidaturaConfig = {
            cargo_id: undefined,
            periodo_electoral_id: undefined,
            territorio_id: undefined,
            departamento_id: undefined,
            municipio_id: undefined,
            localidad_id: undefined,
            territorios_ids: [],
            departamentos_ids: [],
            municipios_ids: [],
            localidades_ids: [],
            multiple: false,
            mostrarVotoBlanco: true,
        };
    }
    if (newType === 'convocatoria' && !newField.convocatoriaConfig) {
        newField.convocatoriaConfig = {
            convocatoria_id: undefined,
            multiple: false,
            mostrarVotoBlanco: props.context === 'votacion', // Solo en votaciones
            filtrarPorUbicacion: props.context === 'candidatura', // Solo en candidaturas
        };
    }
    if (newType === 'file' && !newField.fileConfig) {
        newField.fileConfig = {
            multiple: false,
            maxFiles: 5,
            maxFileSize: 10,
            accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif',
        };
    }
    if (newType === 'datepicker' && !newField.datepickerConfig) {
        newField.datepickerConfig = {
            minDate: undefined,
            maxDate: undefined,
            format: 'DD/MM/YYYY',
            allowPastDates: true,
            allowFutureDates: true,
        };
    }
    if (newType === 'disclaimer' && !newField.disclaimerConfig) {
        newField.disclaimerConfig = {
            disclaimerText: '',
            modalTitle: 'Términos y Condiciones',
            acceptButtonText: 'Acepto',
            declineButtonText: 'No acepto',
        };
    }
    if (newType === 'repeater' && !newField.repeaterConfig) {
        newField.repeaterConfig = {
            minItems: 0,
            maxItems: 10,
            itemName: 'Elemento',
            addButtonText: 'Agregar elemento',
            removeButtonText: 'Eliminar',
            fields: [],
        };
    }
});

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
    if (JSON.stringify(newValue) !== JSON.stringify(fields.value)) {
        setFields(newValue);
    }
}, { deep: true });

// Emit changes to parent
watch(fields, (newFields) => {
    emit('update:modelValue', newFields);
}, { deep: true });

// Handle field addition with validation
const handleAddField = () => {
    if (!newField.title.trim()) return;
    addField();
};

// Handle showing add form with auto-scroll
const handleShowAddForm = () => {
    editingFieldIndex.value = null; // Asegurar que no estamos editando
    showFieldForm.value = true;
    nextTick(() => {
        const formElement = document.getElementById('field-form-add');
        if (formElement) {
            formElement.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center'
            });
        }
    });
};

// Handle editing field with auto-scroll
const handleEditField = (index: number) => {
    editField(index);
    nextTick(() => {
        const formElement = document.getElementById(`field-form-edit-${index}`);
        if (formElement) {
            formElement.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center'
            });
        }
    });
};

// Filtrar tipos de campo disponibles según el contexto
const availableFieldTypes = computed(() => {
    if (props.context === 'votacion') {
        // En votaciones, no mostrar perfil_candidatura, mostrar convocatoria
        return FIELD_TYPES.filter(type => type.value !== 'perfil_candidatura');
    } else if (props.context === 'candidatura') {
        // En candidaturas, mostrar convocatoria pero no perfil_candidatura
        return FIELD_TYPES.filter(type => type.value !== 'perfil_candidatura');
    } else {
        // En convocatorias, mostrar perfil_candidatura pero no convocatoria
        return FIELD_TYPES.filter(type => type.value !== 'convocatoria');
    }
});

onMounted(() => {
    setFields(props.modelValue);
    
    // Asegurar que conditionalConfig existe al montar
    if (!newField.conditionalConfig) {
        newField.conditionalConfig = {
            enabled: false,
            showWhen: 'all',
            conditions: [],
        };
    }
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">{{ title }}</h3>
                <p class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <Button 
                type="button"
                @click="handleShowAddForm" 
                :disabled="disabled"
            >
                <Plus class="mr-2 h-4 w-4" />
                Agregar Campo
            </Button>
        </div>

        <!-- Formulario para agregar campo nuevo (ahora arriba) -->
        <Card v-if="showFieldForm && editingFieldIndex === null" 
              :id="'field-form-add'"
              class="border-2 border-primary/50 shadow-lg">
            <CardHeader>
                <CardTitle class="flex items-center justify-between">
                    Nuevo Campo
                    <Button type="button" variant="ghost" size="sm" @click="resetFieldForm">
                        <X class="h-4 w-4" />
                    </Button>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Contenido del formulario -->
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <Label>Tipo de Campo</Label>
                        <Select v-model="newField.type">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in availableFieldTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-3 mt-6">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="field_required_add"
                                :checked="newField.required"
                                @update:checked="(checked) => newField.required = checked"
                            />
                            <Label>Campo obligatorio</Label>
                        </div>
                        
                        <!-- Opción solo para candidaturas -->
                        <div v-if="showEditableOption" class="flex items-center space-x-2">
                            <Checkbox
                                id="field_editable_add"
                                :checked="newField.editable"
                                @update:checked="(checked) => newField.editable = checked"
                            />
                            <Label class="text-sm">
                                Campo editable en candidaturas aprobadas
                                <span class="text-muted-foreground block text-xs">
                                    Los usuarios podrán editar este campo incluso con candidatura aprobada
                                </span>
                            </Label>
                        </div>
                    </div>
                </div>

                <div>
                    <Label>Título del Campo *</Label>
                    <Input
                        id="field_title_add"
                        v-model="newField.title"
                        placeholder="Ej: ¿Cuál es tu candidato preferido?"
                    />
                </div>

                <div>
                    <Label>Descripción (opcional)</Label>
                    <Textarea
                        id="field_description_add"
                        v-model="newField.description"
                        placeholder="Descripción adicional del campo"
                        rows="2"
                    />
                </div>

                <!-- Categoría/Dimensión del campo -->
                <div>
                    <Label>Categoría/Dimensión (opcional)</Label>
                    <div class="grid grid-cols-1 gap-2">
                        <Input
                            :model-value="newField.category?.name || ''"
                            @update:model-value="(value) => { ensureCategoryObject(); newField.category!.name = value; }"
                            placeholder="Ej: Dim1 - Prioridad y capacidad de gestión..."
                        />
                        <Input
                            :model-value="newField.category?.id || ''"
                            @update:model-value="(value) => { ensureCategoryObject(); newField.category!.id = value; }"
                            placeholder="ID de categoría (ej: dim1)"
                        />
                        <Textarea
                            :model-value="newField.category?.description || ''"
                            @update:model-value="(value) => { ensureCategoryObject(); newField.category!.description = value; }"
                            placeholder="Descripción de la categoría (opcional)"
                            rows="2"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        La categoría permite agrupar preguntas para análisis estadístico por dimensiones.
                    </p>
                </div>

                <!-- Opciones para select, radio, checkbox -->
                <div v-if="['select', 'radio', 'checkbox'].includes(newField.type)">
                    <div class="flex items-center justify-between">
                        <Label>Opciones</Label>
                        <Button type="button" variant="outline" size="sm" @click="addOption">
                            <Plus class="mr-2 h-3 w-3" />
                            Agregar Opción
                        </Button>
                    </div>
                    <div class="space-y-2 mt-2">
                        <div
                            v-for="(option, index) in newField.options"
                            :key="index"
                            class="flex gap-2 items-center"
                        >
                            <div class="flex-1">
                                <Input
                                    v-model="(newField.options![index] as any).label"
                                    placeholder="Texto de la opción"
                                />
                            </div>
                            <div class="w-24">
                                <Input
                                    v-model.number="(newField.options![index] as any).value"
                                    type="number"
                                    placeholder="Valor"
                                    class="text-center"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="removeOption(index)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            El valor numérico es opcional y se usa para estadísticas y cálculos.
                        </p>
                    </div>
                </div>

                <!-- Configuración condicional (para todos los campos excepto el repetidor y campos especiales) -->
                <div v-if="!['repeater', 'perfil_candidatura', 'convocatoria'].includes(newField.type)">
                    <ConditionalFieldConfig
                        v-model="newField.conditionalConfig"
                        :fields="fields"
                        :current-field-id="newField.id || 'new_field'"
                    />
                </div>

                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="resetFieldForm">
                        Cancelar
                    </Button>
                    <Button type="button" @click="handleAddField" :disabled="!newField.title.trim()">
                        Agregar Campo
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Lista de campos existentes -->
        <div v-if="fields.length > 0" class="space-y-4">
            <template v-for="(field, index) in fields" :key="field.id">
                <!-- Campo existente -->
                <div
                    class="border rounded-lg p-4 flex items-center justify-between"
                >
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <Badge variant="outline">{{ availableFieldTypes.find(t => t.value === field.type)?.label || field.type }}</Badge>
                        <span v-if="field.required" class="text-red-500">*</span>
                        <Badge v-if="showEditableOption && field.editable" variant="secondary" class="text-xs bg-green-100 text-green-800">
                            Editable
                        </Badge>
                        <Badge v-if="field.conditionalConfig?.enabled" variant="secondary" class="text-xs bg-orange-100 text-orange-800">
                            <GitBranch class="h-3 w-3 mr-1" />
                            Condicional
                        </Badge>
                        <Badge v-if="field.type === 'convocatoria' && field.convocatoriaConfig?.convocatoria_id" variant="secondary" class="text-xs">
                            Conv. ID: {{ field.convocatoriaConfig.convocatoria_id }}
                        </Badge>
                    </div>
                    <h4 class="font-medium">{{ field.title }}</h4>
                    <p v-if="field.description" class="text-sm text-muted-foreground">
                        {{ field.description }}
                    </p>
                    <div v-if="field.options && field.options.length > 0" class="mt-2">
                        <p class="text-xs text-muted-foreground">Opciones:</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <Badge v-for="option in field.options" :key="option" variant="secondary" class="text-xs">
                                {{ option }}
                            </Badge>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button 
                        type="button"
                        variant="ghost" 
                        size="sm" 
                        @click="handleEditField(index)"
                        :disabled="disabled"
                    >
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button 
                        type="button"
                        variant="ghost" 
                        size="sm" 
                        @click="removeField(index)"
                        :disabled="disabled"
                    >
                        <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                </div>
                </div>
                
                <!-- Formulario de edición inline -->
                <Card v-if="showFieldForm && editingFieldIndex === index" 
                      :id="`field-form-edit-${index}`"
                      class="border-2 border-primary/50 shadow-lg ml-4">
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between">
                            Editar Campo
                            <Button type="button" variant="ghost" size="sm" @click="resetFieldForm">
                                <X class="h-4 w-4" />
                            </Button>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Contenido del formulario de edición -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Tipo de Campo</Label>
                                <Select v-model="newField.type">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="type in availableFieldTypes"
                                            :key="type.value"
                                            :value="type.value"
                                        >
                                            {{ type.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-3 mt-6">
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        :id="`field_required_edit_${index}`"
                                        :checked="newField.required"
                                        @update:checked="(checked) => newField.required = checked"
                                    />
                                    <Label>Campo obligatorio</Label>
                                </div>
                                
                                <!-- Opción solo para candidaturas -->
                                <div v-if="showEditableOption" class="flex items-center space-x-2">
                                    <Checkbox
                                        :id="`field_editable_edit_${index}`"
                                        :checked="newField.editable"
                                        @update:checked="(checked) => newField.editable = checked"
                                    />
                                    <Label class="text-sm">
                                        Campo editable en candidaturas aprobadas
                                        <span class="text-muted-foreground block text-xs">
                                            Los usuarios podrán editar este campo incluso con candidatura aprobada
                                        </span>
                                    </Label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <Label>Título del Campo *</Label>
                            <Input
                                :id="`field_title_edit_${index}`"
                                v-model="newField.title"
                                placeholder="Ej: ¿Cuál es tu candidato preferido?"
                            />
                        </div>

                        <div>
                            <Label>Descripción (opcional)</Label>
                            <Textarea
                                :id="`field_description_edit_${index}`"
                                v-model="newField.description"
                                placeholder="Descripción adicional del campo"
                                rows="2"
                            />
                        </div>

                        <!-- Categoría/Dimensión del campo -->
                        <div>
                            <Label>Categoría/Dimensión (opcional)</Label>
                            <div class="grid grid-cols-1 gap-2">
                                <Input
                                    :model-value="newField.category?.name || ''"
                                    @update:model-value="(value) => { ensureCategoryObject(); newField.category!.name = value; }"
                                    placeholder="Ej: Dim1 - Prioridad y capacidad de gestión..."
                                />
                                <Input
                                    :model-value="newField.category?.id || ''"
                                    @update:model-value="(value) => { ensureCategoryObject(); newField.category!.id = value; }"
                                    placeholder="ID de categoría (ej: dim1)"
                                />
                                <Textarea
                                    :model-value="newField.category?.description || ''"
                                    @update:model-value="(value) => { ensureCategoryObject(); newField.category!.description = value; }"
                                    placeholder="Descripción de la categoría (opcional)"
                                    rows="2"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">
                                La categoría permite agrupar preguntas para análisis estadístico por dimensiones.
                            </p>
                        </div>

                        <!-- Opciones para select, radio, checkbox -->
                        <div v-if="['select', 'radio', 'checkbox'].includes(newField.type)">
                            <div class="flex items-center justify-between">
                                <Label>Opciones</Label>
                                <Button type="button" variant="outline" size="sm" @click="addOption">
                                    <Plus class="mr-2 h-3 w-3" />
                                    Agregar Opción
                                </Button>
                            </div>
                            <div class="space-y-2 mt-2">
                                <div
                                    v-for="(option, optionIdx) in newField.options"
                                    :key="optionIdx"
                                    class="flex gap-2 items-center"
                                >
                                    <div class="flex-1">
                                        <Input
                                            v-model="(newField.options![optionIdx] as any).label"
                                            placeholder="Texto de la opción"
                                        />
                                    </div>
                                    <div class="w-24">
                                        <Input
                                            v-model.number="(newField.options![optionIdx] as any).value"
                                            type="number"
                                            placeholder="Valor"
                                            class="text-center"
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="removeOption(optionIdx)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    El valor numérico es opcional y se usa para estadísticas y cálculos.
                                </p>
                            </div>
                        </div>

                        <!-- Configuración condicional (para todos los campos excepto el repetidor y campos especiales) -->
                        <div v-if="!['repeater', 'perfil_candidatura', 'convocatoria'].includes(newField.type)">
                            <ConditionalFieldConfig
                                v-model="newField.conditionalConfig"
                                :fields="fields"
                                :current-field-id="newField.id || 'new_field'"
                            />
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="resetFieldForm">
                                Cancelar
                            </Button>
                            <Button type="button" @click="handleAddField" :disabled="!newField.title.trim()">
                                Actualizar Campo
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>

        <!-- Mensaje cuando no hay campos -->
        <div v-else class="text-center py-8 text-muted-foreground border-2 border-dashed rounded-lg">
            <p>No hay campos configurados</p>
            <p class="text-sm">Agrega al menos un campo para continuar</p>
        </div>
    </div>
</template>
