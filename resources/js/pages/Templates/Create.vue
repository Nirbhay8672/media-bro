<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import templates from '@/routes/templates/index';
import { type BreadcrumbItem } from '@/types';
import { ref, reactive, computed, watch, onMounted } from 'vue';
import TemplateSettings from '@/components/TemplateSettings.vue';
import CanvasEditor from '@/components/CanvasEditor.vue';
import ToolsPanel from '@/components/ToolsPanel.vue';
import PropertiesPanel from '@/components/PropertiesPanel.vue';
import Swal from 'sweetalert2';

// Props for edit mode
const props = defineProps<{
    template?: {
        id: number;
        name: string;
        description?: string;
        width: number;
        height: number;
        background_image?: string;
        canvas_data: any[] | string;
        share_token: string;
        is_active: boolean;
        created_at: string;
        updated_at: string;
    };
}>();

const page = usePage();
const isEditMode = computed(() => !!props.template);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Templates',
        href: templates.index().url,
    },
    {
        title: isEditMode.value ? 'Edit Template' : 'Create Template',
        href: '#',
    },
]);

const form = ref({
    name: '',
    description: '',
    width: 800,
    height: 600,
    background_image: null as File | null,
    canvas_data: [] as any[],
});

const backgroundImagePreview = ref<string | null>(null);

// Initialize form data for edit mode
onMounted(() => {
    if (isEditMode.value && props.template) {
        form.value.name = props.template.name;
        form.value.description = props.template.description || '';
        form.value.width = props.template.width;
        form.value.height = props.template.height;
        
        // Parse canvas data if it's a string
        let canvasData = props.template.canvas_data;
        if (typeof canvasData === 'string') {
            try {
                canvasData = JSON.parse(canvasData);
            } catch (error) {
                console.error('Error parsing canvas_data:', error);
                canvasData = [];
            }
        }
        canvasElements.value = Array.isArray(canvasData) ? canvasData : [];
        
        // Set background image preview if exists
        if (props.template.background_image) {
            backgroundImagePreview.value = `/storage/${props.template.background_image}`;
        }
    }
});

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
    type: 'text' | 'image' | 'rectangle' | 'circle' | 'triangle' | 'star';
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
        imageShape?: string;
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
            backgroundColor: type === 'text' ? 'transparent' : (type === 'image' ? 'transparent' : '#000000'),
            imageShape: type === 'image' ? 'rectangle' : undefined
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

// Resize element handler
const handleResizeElement = (elementId: string, width: number, height: number, x: number, y: number, fontSize?: number) => {
    const element = canvasElements.value.find(el => el.id === elementId);
    if (element) {
        element.width = width;
        element.height = height;
        element.x = x;
        element.y = y;
        
        // Update font size for text elements
        if (element.type === 'text' && fontSize !== undefined) {
            element.properties.fontSize = fontSize;
        }
        
        if (selectedElement.value?.id === elementId) {
            selectedElement.value = { ...element };
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
        Swal.fire({
            title: 'Invalid Dimensions',
            text: 'Canvas dimensions must be at least 100x100 pixels',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }

    if (!form.value.name.trim()) {
        Swal.fire({
            title: 'Missing Template Name',
            text: 'Please enter a template name',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Show loading state
    Swal.fire({
        title: isEditMode.value ? 'Updating Template...' : 'Creating Template...',
        text: isEditMode.value ? 'Please wait while we update your template.' : 'Please wait while we save your template.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    isSubmitting.value = true;

    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('description', form.value.description);
    formData.append('width', form.value.width.toString());
    formData.append('height', form.value.height.toString());
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    
    // Debug logging
    console.log('Canvas elements being sent:', canvasElements.value);
    console.log('Canvas dimensions:', { width: form.value.width, height: form.value.height });
    
    formData.append('canvas_data', JSON.stringify(canvasElements.value));

    if (form.value.background_image) {
        formData.append('background_image', form.value.background_image);
    }

    try {
        const url = isEditMode.value ? `/templates/${props.template?.id}` : '/templates';
        const method = 'post';
        
        console.log('Submitting form:', {
            url,
            method,
            isEditMode: isEditMode.value,
            templateId: props.template?.id,
            formData: Object.fromEntries(formData.entries())
        });
        
        await router[method](url, formData, {
            onSuccess: (page) => {
                console.log('Form submission successful:', page);
                Swal.fire({
                    title: 'Success!',
                    text: `Template ${isEditMode.value ? 'updated' : 'created'} successfully.`,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Navigate back to templates index after success
                    if (isEditMode.value) {
                        window.location.href = '/templates';
                    }
                });
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
                Swal.fire({
                    title: 'Error!',
                    text: `Failed to ${isEditMode.value ? 'update' : 'create'} template. Please try again.`,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            onFinish: () => {
                console.log('Form submission finished');
            }
        });
    } catch (error) {
        console.error('Form submission error:', error);
        Swal.fire({
            title: 'Error!',
            text: 'An unexpected error occurred. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
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
    <Head :title="isEditMode ? 'Edit Template' : 'Create Template'" />

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
                        :is-edit-mode="isEditMode"
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
                        @resizeElement="handleResizeElement"
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
