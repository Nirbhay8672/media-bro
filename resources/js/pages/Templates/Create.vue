<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import templates from '@/routes/templates';
import { type BreadcrumbItem } from '@/types';
import { ref, reactive, computed, watch } from 'vue';
import TemplateSettings from '@/components/TemplateSettings.vue';
import CanvasEditor from '@/components/CanvasEditor.vue';
import ToolsPanel from '@/components/ToolsPanel.vue';
import PropertiesPanel from '@/components/PropertiesPanel.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Templates',
        href: templates.index().url,
    },
    {
        title: 'Create Template',
        href: '#',
    },
];

const form = ref({
    name: '',
    description: '',
    width: 800,
    height: 600,
    background_image: null as File | null,
    canvas_data: [] as any[],
});

const backgroundImagePreview = ref<string | null>(null);

// Editor state
const selectedTool = ref('select');
const selectedElement = ref<CanvasElement | null>(null);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const isSubmitting = ref(false);
const isResizing = ref(false);
const resizeHandle = ref('');
const resizeStart = ref({ x: 0, y: 0, width: 0, height: 0, fontSize: 0 });

// Quick template dropdown state
const isQuickTemplateDropdownOpen = ref(false);
const selectedQuickTemplate = ref<{ name: string; width: number; height: number } | null>(null);

// Canvas elements
const canvasElements = ref<CanvasElement[]>([]);

// CanvasElement interface
interface CanvasElement {
    id: string;
    type: 'text' | 'image' | 'rectangle' | 'circle' | 'triangle' | 'star' | 'heart';
    x: number;
    y: number;
    width: number;
    height: number;
    rotation: number;
    zIndex: number;
    properties: {
        text?: string;
        fontSize?: number;
        fontFamily?: string;
        fontWeight?: string;
        fontStyle?: string;
        textDecoration?: string;
        textAlign?: string;
        lineHeight?: number;
        color?: string;
        fillColor?: string;
        strokeColor?: string;
        strokeWidth?: number;
        hasBorder?: boolean;
        borderWidth?: number;
        borderStyle?: string;
        borderColor?: string;
        borderRadius?: number;
        boxShadow?: string;
        textShadow?: string;
        imageUrl?: string;
        imageFit?: string;
        backgroundColor?: string;
    };
}

// Watch for changes in selectedElement to force reactivity
watch(selectedElement, () => {
    // Force reactivity update
}, { deep: true });

// Element creation
const createElement = (type: CanvasElement['type'], x: number, y: number) => {
    const maxZIndex = canvasElements.value.length > 0 ? Math.max(...canvasElements.value.map(el => el.zIndex)) : 0;
    
    const newElement: CanvasElement = {
        id: Date.now().toString(),
        type,
        x,
        y,
        width: type === 'text' ? 200 : 100,
        height: type === 'text' ? 50 : 100,
        rotation: 0,
        zIndex: maxZIndex + 1,
        properties: {
            text: type === 'text' ? 'Sample Text' : '',
            fontSize: type === 'text' ? 16 : undefined,
            fontFamily: type === 'text' ? 'Arial' : undefined,
            fontWeight: type === 'text' ? 'normal' : undefined,
            fontStyle: type === 'text' ? 'normal' : undefined,
            textDecoration: type === 'text' ? 'none' : undefined,
            textAlign: type === 'text' ? 'left' : undefined,
            lineHeight: type === 'text' ? 1.2 : undefined,
            color: type === 'text' ? '#000000' : undefined,
            fillColor: type === 'text' ? 'transparent' : '#000000',
            strokeColor: '#000000',
            strokeWidth: 1,
            hasBorder: false,
            borderWidth: 1,
            borderStyle: 'solid',
            borderColor: '#000000',
            borderRadius: 0,
            boxShadow: 'none',
            textShadow: 'none',
            imageUrl: type === 'image' ? '' : undefined,
            imageFit: type === 'image' ? 'contain' : undefined,
            backgroundColor: type === 'text' ? 'transparent' : (type === 'image' ? 'transparent' : '#000000')
        }
    };
    
    canvasElements.value.push(newElement);
    selectedElement.value = newElement;
    selectedTool.value = 'select';
};

// Element selection
const selectElement = (element: CanvasElement) => {
    selectedElement.value = element;
    selectedTool.value = 'select';
};

// Element property updates
const updateElementProperty = (elementId: string, property: string, value: any) => {
    const elementIndex = canvasElements.value.findIndex(el => el.id === elementId);
    if (elementIndex !== -1) {
        const element = canvasElements.value[elementIndex];
        element.properties = { ...element.properties, [property]: value };
        canvasElements.value[elementIndex] = { ...element };
        
    if (selectedElement.value?.id === elementId) {
            selectedElement.value = { ...element };
        }
    }
};

// Canvas click handler
const handleCanvasClick = (event: MouseEvent) => {
    if (selectedTool.value !== 'select') {
        const rect = (event.target as HTMLElement).getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        createElement(selectedTool.value as CanvasElement['type'], x, y);
    }
};

// Element mouse down handler
const handleElementMouseDown = (event: MouseEvent, element: CanvasElement) => {
    if (selectedTool.value === 'select') {
        event.preventDefault();
        isDragging.value = true;
        dragStart.value = {
            x: event.clientX - element.x,
            y: event.clientY - element.y
        };
        selectedElement.value = element;
    }
};

// Mouse move handler
const handleMouseMove = (event: MouseEvent) => {
    if (isDragging.value && selectedElement.value) {
        selectedElement.value.x = event.clientX - dragStart.value.x;
        selectedElement.value.y = event.clientY - dragStart.value.y;
    }
};

// Mouse up handler
const handleMouseUp = (event: MouseEvent) => {
    isDragging.value = false;
};

// Element manipulation functions
const bringToFront = (elementId: string) => {
    const element = canvasElements.value.find(el => el.id === elementId);
    if (element) {
        const maxZIndex = Math.max(...canvasElements.value.map(el => el.zIndex));
        element.zIndex = maxZIndex + 1;
    }
};

const sendToBack = (elementId: string) => {
    const element = canvasElements.value.find(el => el.id === elementId);
    if (element) {
        const minZIndex = Math.min(...canvasElements.value.map(el => el.zIndex));
        element.zIndex = minZIndex - 1;
    }
};

const duplicateElement = (elementId: string) => {
    const element = canvasElements.value.find(el => el.id === elementId);
    if (element) {
        const maxZIndex = Math.max(...canvasElements.value.map(el => el.zIndex));
        const duplicatedElement: CanvasElement = {
            ...element,
            id: Date.now().toString(),
            x: element.x + 20,
            y: element.y + 20,
            zIndex: maxZIndex + 1
        };
        canvasElements.value.push(duplicatedElement);
        selectedElement.value = duplicatedElement;
    }
};

const deleteElement = (elementId: string) => {
    const elementIndex = canvasElements.value.findIndex(el => el.id === elementId);
    if (elementIndex !== -1) {
        canvasElements.value.splice(elementIndex, 1);
        if (selectedElement.value?.id === elementId) {
            selectedElement.value = null;
        }
    }
};


// Quick template functions
const applyQuickTemplate = (template: { name: string; width: number; height: number }) => {
    selectedQuickTemplate.value = template;
    if (template.name !== 'Custom') {
        form.value.width = template.width;
        form.value.height = template.height;
    }
    isQuickTemplateDropdownOpen.value = false;
};

const handleBackgroundImageChange = (file: File) => {
    form.value.background_image = file;
};

// Form submission
const submitForm = async () => {
    if (form.value.width < 100 || form.value.height < 100) {
        alert('Canvas dimensions must be at least 100x100 pixels');
        return;
    }

    isSubmitting.value = true;

    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('description', form.value.description);
    formData.append('width', form.value.width.toString());
    formData.append('height', form.value.height.toString());
    formData.append('canvas_data', JSON.stringify(canvasElements.value));

    if (form.value.background_image) {
        formData.append('background_image', form.value.background_image);
    }

    try {
        await router.post('/templates', formData, {
            onSuccess: () => {
                // Handle success
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
            }
        });
    } catch (error) {
        console.error('Form submission error:', error);
    } finally {
        isSubmitting.value = false;
    }
};

// Dropdown handlers
const closeDropdownOnOutsideClick = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.quick-template-dropdown')) {
        isQuickTemplateDropdownOpen.value = false;
    }
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        isQuickTemplateDropdownOpen.value = false;
    }
};
</script>

<template>
    <Head title="Create Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="h-screen bg-gray-50 dark:bg-gray-900 overflow-hidden" @click="closeDropdownOnOutsideClick" @keydown="handleKeydown">
            <!-- Main Content -->
            <div class="w-full px-4 py-4">
                <form @submit.prevent="submitForm" class="flex gap-4">
                    <!-- Left Sidebar - Template Settings -->
                    <TemplateSettings
                        v-model:form="form"
                        v-model:backgroundImagePreview="backgroundImagePreview"
                        v-model:selectedQuickTemplate="selectedQuickTemplate"
                        v-model:isQuickTemplateDropdownOpen="isQuickTemplateDropdownOpen"
                        @backgroundImageChange="handleBackgroundImageChange"
                        @applyQuickTemplate="applyQuickTemplate"
                    />

                    <!-- Center - Canvas Editor -->
                    <CanvasEditor
                        :form="form"
                        :backgroundImagePreview="backgroundImagePreview"
                        :canvasElements="canvasElements"
                        :selectedElement="selectedElement"
                        :selectedTool="selectedTool"
                        @canvasClick="handleCanvasClick"
                        @elementMouseDown="handleElementMouseDown"
                        @mouseMove="handleMouseMove"
                        @mouseUp="handleMouseUp"
                        @selectElement="selectElement"
                        @bringToFront="bringToFront"
                        @sendToBack="sendToBack"
                        @duplicateElement="duplicateElement"
                        @deleteElement="deleteElement"
                    />

                    <!-- Right Sidebar - Tools & Properties -->
                    <div class="w-72 flex-shrink-0">
                        <div class="space-y-3">
                            <!-- Tools -->
                            <ToolsPanel
                                v-model:selectedTool="selectedTool"
                            />

                            <!-- Element Properties -->
                            <PropertiesPanel
                                :selectedElement="selectedElement"
                                @updateElement="updateElementProperty"
                                @bringToFront="bringToFront"
                                @sendToBack="sendToBack"
                                @duplicateElement="duplicateElement"
                                @deleteElement="deleteElement"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
