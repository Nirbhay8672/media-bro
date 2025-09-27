<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import templates from '@/routes/templates';
import { type BreadcrumbItem } from '@/types';
import {
    ArrowLeft, Save, Upload, Trash2, Type, Square, Circle, Triangle, Star, Heart,
    Brush, Image, AlignLeft, AlignCenter, AlignRight, Bold, Italic, Underline,
    Move, RotateCw, Copy, Trash, Layers, Palette, Eye, EyeOff
} from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';

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
        title: 'Edit Template',
        href: '#',
    },
];

interface Template {
    id: number;
    name: string;
    description?: string;
    width: number;
    height: number;
    background_image?: string;
    canvas_data: any[];
    share_token: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface CanvasElement {
    id: string;
    type: 'text' | 'rectangle' | 'circle' | 'triangle' | 'star' | 'heart' | 'brush' | 'image';
    x: number;
    y: number;
    width: number;
    height: number;
    rotation: number;
    zIndex: number;
    properties: {
        // Text properties
        text?: string;
        fontSize?: number;
        fontFamily?: string;
        fontWeight?: string;
        fontStyle?: string;
        textDecoration?: string;
        textAlign?: string;
        lineHeight?: number;

        // Color properties
        color?: string;
        backgroundColor?: string;

        // Border properties
        hasBorder?: boolean;
        borderWidth?: number;
        borderColor?: string;
        borderStyle?: string;
        borderRadius?: number;

        // Shadow properties
        boxShadow?: string;
        textShadow?: string;

        // Image properties
        imageUrl?: string;
        imageFit?: string;
        imagePlaceholder?: string;

        // Shape properties
        shapeType?: string;
        fillColor?: string;
        strokeColor?: string;
        strokeWidth?: number;

        // Brush properties
        brushSize?: number;
        brushColor?: string;
        brushOpacity?: number;
    };
}

const props = defineProps<{
    template: Template;
}>();

const form = ref({
    name: props.template.name,
    description: props.template.description || '',
    width: props.template.width,
    height: props.template.height,
    background_image: null as File | null,
    canvas_data: props.template.canvas_data || [],
});

const backgroundImagePreview = ref<string | null>(
    props.template.background_image ? `/storage/${props.template.background_image}` : null
);

// Editor state
const selectedTool = ref('select');
const selectedElement = ref<CanvasElement | null>(null);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const isSubmitting = ref(false);
const isResizing = ref(false);
const resizeHandle = ref('');
const resizeStart = ref({ x: 0, y: 0, width: 0, height: 0, fontSize: 0 });

// Canvas elements
const canvasElements = ref<CanvasElement[]>([]);

// Initialize canvas elements with proper defaults
const initializeCanvasElements = () => {
    const templateData = Array.isArray(props.template.canvas_data) ? props.template.canvas_data : [];
    canvasElements.value = templateData.map((element: any) => ({
        ...element,
        properties: {
            // Text properties
            text: element.properties?.text || '',
            fontSize: element.properties?.fontSize || 16,
            fontFamily: element.properties?.fontFamily || 'Arial',
            fontWeight: element.properties?.fontWeight || 'normal',
            fontStyle: element.properties?.fontStyle || 'normal',
            textDecoration: element.properties?.textDecoration || 'none',
            textAlign: element.properties?.textAlign || 'left',
            lineHeight: element.properties?.lineHeight || 1.2,

            // Color properties
            color: element.properties?.color || '#000000',
            backgroundColor: element.properties?.backgroundColor || (element.type === 'text' ? 'transparent' : '#000000'),

            // Border properties
            hasBorder: element.properties?.hasBorder || false,
            borderWidth: element.properties?.borderWidth || 1,
            borderColor: element.properties?.borderColor || '#000000',
            borderStyle: element.properties?.borderStyle || 'solid',
            borderRadius: element.properties?.borderRadius || 0,

            // Shadow properties
            boxShadow: element.properties?.boxShadow || 'none',
            textShadow: element.properties?.textShadow || 'none',

            // Image properties
            imageUrl: element.properties?.imageUrl || '',
            imageFit: element.properties?.imageFit || 'cover',
            imagePlaceholder: element.properties?.imagePlaceholder || (element.type === 'image' ? 'Upload Image' : ''),

            // Shape properties
            shapeType: element.properties?.shapeType || element.type,
            fillColor: element.properties?.fillColor || '#000000',
            strokeColor: element.properties?.strokeColor || '#000000',
            strokeWidth: element.properties?.strokeWidth || 1,

            // Brush properties
            brushSize: element.properties?.brushSize || 5,
            brushColor: element.properties?.brushColor || '#000000',
            brushOpacity: element.properties?.brushOpacity || 1,
        }
    }));
};

// Initialize on mount
initializeCanvasElements();

// Tools
const tools = [
    { id: 'select', name: 'Select', icon: Move },
    { id: 'text', name: 'Text', icon: Type },
    { id: 'rectangle', name: 'Rectangle', icon: Square },
    { id: 'circle', name: 'Circle', icon: Circle },
    { id: 'triangle', name: 'Triangle', icon: Triangle },
    { id: 'star', name: 'Star', icon: Star },
    { id: 'heart', name: 'Heart', icon: Heart },
    { id: 'brush', name: 'Brush', icon: Brush },
    { id: 'image', name: 'Image', icon: Image },
];

// Font families
const fontFamilies = [
    'Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana',
    'Trebuchet MS', 'Arial Black', 'Impact', 'Comic Sans MS', 'Courier New'
];

// Shadow presets
const shadowPresets = [
    { name: 'None', value: 'none' },
    { name: 'Small', value: '0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)' },
    { name: 'Medium', value: '0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23)' },
    { name: 'Large', value: '0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23)' },
    { name: 'Extra Large', value: '0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22)' },
];

// Border styles
const borderStyles = [
    { name: 'Solid', value: 'solid' },
    { name: 'Dashed', value: 'dashed' },
    { name: 'Dotted', value: 'dotted' },
    { name: 'Double', value: 'double' },
];

// Color palette
const colorPalette = [
    '#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00',
    '#FF00FF', '#00FFFF', '#FFA500', '#800080', '#FFC0CB', '#A52A2A',
    '#808080', '#FFD700', '#008000', '#000080', '#FF6347', '#40E0D0'
];

// Quick templates
const quickTemplates = [
    { name: 'Instagram Post', width: 1080, height: 1080 },
    { name: 'Instagram Story', width: 1080, height: 1920 },
    { name: 'Facebook Post', width: 1200, height: 630 },
    { name: 'Twitter Header', width: 1500, height: 500 },
    { name: 'YouTube Thumbnail', width: 1280, height: 720 },
    { name: 'LinkedIn Post', width: 1200, height: 627 },
];

const handleBackgroundImage = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.value.background_image = target.files[0];

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            backgroundImagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(target.files[0]);
    }
};

const removeBackgroundImage = () => {
    form.value.background_image = null;
    backgroundImagePreview.value = null;
};

// Create new element
const createElement = (type: string) => {
    const maxZIndex = Math.max(...canvasElements.value.map(el => el.zIndex), 0);

    const element: CanvasElement = {
        id: `element_${Date.now()}`,
        type: type as any,
        x: 100,
        y: 100,
        width: type === 'text' ? 200 : (type === 'brush' ? 50 : 100),
        height: type === 'text' ? 50 : (type === 'brush' ? 50 : 100),
        rotation: 0,
        zIndex: maxZIndex + 1,
        properties: {
            // Text properties
            text: type === 'text' ? 'Sample Text' : '',
            fontSize: 16,
            fontFamily: 'Arial',
            fontWeight: 'normal',
            fontStyle: 'normal',
            textDecoration: 'none',
            textAlign: 'left',
            lineHeight: 1.2,

            // Color properties
            color: '#000000',
            backgroundColor: type === 'text' ? 'transparent' : '#000000',

            // Border properties
            hasBorder: false,
            borderWidth: 1,
            borderColor: '#000000',
            borderStyle: 'solid',
            borderRadius: 0,

            // Shadow properties
            boxShadow: 'none',
            textShadow: 'none',

            // Image properties
            imageUrl: '',
            imageFit: 'cover',
            imagePlaceholder: type === 'image' ? 'Upload Image' : '',

            // Shape properties
            shapeType: type,
            fillColor: '#000000',
            strokeColor: '#000000',
            strokeWidth: 1,

            // Brush properties
            brushSize: 5,
            brushColor: '#000000',
            brushOpacity: 1,
        }
    };

    canvasElements.value.push(element);
    selectedElement.value = element;
    selectedTool.value = 'select';
};

// Select element
const selectElement = (element: CanvasElement) => {
    selectedElement.value = element;
    selectedTool.value = 'select';
};

// Delete element
const deleteElement = (elementId: string) => {
    const index = canvasElements.value.findIndex(el => el.id === elementId);
    if (index > -1) {
        canvasElements.value.splice(index, 1);
    }
    if (selectedElement.value?.id === elementId) {
        selectedElement.value = null;
    }
};

// Update element properties
const updateElementProperty = (elementId: string, property: string, value: any) => {
    const element = canvasElements.value.find(el => el.id === elementId);
    if (element) {
        element.properties[property] = value;
    }
};

// Layer controls
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

const bringForward = (elementId: string) => {
    const element = canvasElements.value.find(el => el.id === elementId);
    if (element) {
        element.zIndex += 1;
    }
};

const sendBackward = (elementId: string) => {
    const element = canvasElements.value.find(el => el.id === elementId);
    if (element) {
        element.zIndex -= 1;
    }
};

// Duplicate element
const duplicateElement = (elementId: string) => {
    const element = canvasElements.value.find(el => el.id === elementId);
    if (element) {
        const duplicated: CanvasElement = {
            ...element,
            id: `element_${Date.now()}`,
            x: element.x + 20,
            y: element.y + 20,
            zIndex: Math.max(...canvasElements.value.map(el => el.zIndex)) + 1
        };
        canvasElements.value.push(duplicated);
        selectedElement.value = duplicated;
    }
};

// Quick template
const applyQuickTemplate = (template: any) => {
    form.value.width = template.width;
    form.value.height = template.height;
};

// Mouse events
const handleMouseDown = (event: MouseEvent) => {
    if (selectedTool.value === 'select') return;

    const rect = (event.target as HTMLElement).getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    if (selectedTool.value !== 'select') {
        createElement(selectedTool.value);
        const newElement = canvasElements.value[canvasElements.value.length - 1];
        newElement.x = x - newElement.width / 2;
        newElement.y = y - newElement.height / 2;
        selectedElement.value = newElement;
        selectedTool.value = 'select';
    }
};

const handleMouseMove = (event: MouseEvent) => {
    if (isDragging.value && selectedElement.value) {
        const rect = (event.target as HTMLElement).getBoundingClientRect();
        selectedElement.value.x = event.clientX - rect.left - dragStart.value.x;
        selectedElement.value.y = event.clientY - rect.top - dragStart.value.y;
    }

    if (isResizing.value && selectedElement.value) {
        handleResizeMove(event);
    }
};

const handleMouseUp = () => {
    isDragging.value = false;
    isResizing.value = false;
    resizeHandle.value = '';
};

// Drag functionality
const handleDragStart = (event: MouseEvent, element: CanvasElement) => {
    event.stopPropagation();
    selectedElement.value = element;
    isDragging.value = true;
    dragStart.value = {
        x: event.clientX - element.x,
        y: event.clientY - element.y
    };
};

// Resize functionality
const handleResizeStart = (event: MouseEvent, element: CanvasElement, handle: string) => {
    event.stopPropagation();
    selectedElement.value = element;
    isResizing.value = true;
    resizeHandle.value = handle;
    resizeStart.value = {
        x: event.clientX,
        y: event.clientY,
        width: element.width,
        height: element.height,
        fontSize: element.type === 'text' ? (element.properties.fontSize || 16) : 0
    };
};

const handleResizeMove = (event: MouseEvent) => {
    if (isResizing.value && selectedElement.value) {
        const deltaX = event.clientX - resizeStart.value.x;
        const deltaY = event.clientY - resizeStart.value.y;

        // Only bottom-right corner resize with proportional scaling
        if (resizeHandle.value === 'se') {
            // Calculate the scale factor based on the larger dimension change
            const scaleX = (resizeStart.value.width + deltaX) / resizeStart.value.width;
            const scaleY = (resizeStart.value.height + deltaY) / resizeStart.value.height;

            // Use the smaller scale factor to maintain aspect ratio
            const scale = Math.min(scaleX, scaleY);

            // Apply proportional scaling
            selectedElement.value.width = Math.max(20, resizeStart.value.width * scale);
            selectedElement.value.height = Math.max(20, resizeStart.value.height * scale);

            // For text elements, also scale the font size proportionally
            if (selectedElement.value.type === 'text') {
                selectedElement.value.properties.fontSize = Math.max(8, Math.round(resizeStart.value.fontSize * scale));
            }
        }
    }
};

// Computed properties
const sortedElements = computed(() => {
    return [...canvasElements.value].sort((a, b) => a.zIndex - b.zIndex);
});

const submitForm = () => {
    if (!form.value.name.trim()) {
        alert('Please enter a template name');
        return;
    }

    if (form.value.width < 100 || form.value.height < 100) {
        alert('Canvas dimensions must be at least 100x100 pixels');
        return;
    }

    if (!confirm('Are you sure you want to update this template?')) {
        return;
    }

    isSubmitting.value = true;

    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('description', form.value.description);
    formData.append('width', form.value.width.toString());
    formData.append('height', form.value.height.toString());
    formData.append('canvas_data', JSON.stringify(canvasElements.value));
    formData.append('_method', 'PUT');

    if (form.value.background_image) {
        formData.append('background_image', form.value.background_image);
    }

    router.post(templates.update(props.template.id), formData, {
        forceFormData: true,
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <Head title="Edit Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <router-link
                                :href="templates.index()"
                                class="rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <ArrowLeft class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            </router-link>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Template</h1>
                                <p class="text-gray-600 dark:text-gray-400">Modify your template with the full editor</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="router.visit(templates.index())"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                form="template-form"
                                :disabled="isSubmitting"
                                class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
                            >
                                <Save class="h-4 w-4" />
                                {{ isSubmitting ? 'Updating...' : 'Update Template' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <form id="template-form" @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Sidebar - Template Settings -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Template Settings</h2>
                            </div>
                            <div class="p-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Template Name *
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors text-sm"
                                        placeholder="Enter template name"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors text-sm"
                                        placeholder="Enter template description"
                                    ></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Width (px) *
                                        </label>
                                        <input
                                            v-model.number="form.width"
                                            type="number"
                                            step="0.5"
                                            min="100"
                                            max="4000"
                                            required
                                            class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Height (px) *
                                        </label>
                                        <input
                                            v-model.number="form.height"
                                            type="number"
                                            step="0.5"
                                            min="100"
                                            max="4000"
                                            required
                                            class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Background Image
                                    </label>
                                    <input
                                        @change="handleBackgroundImage"
                                        type="file"
                                        accept="image/*"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-300"
                                    />
                                    <div v-if="backgroundImagePreview" class="mt-2">
                                        <div class="relative">
                                            <img
                                                :src="backgroundImagePreview"
                                                alt="Background preview"
                                                class="h-24 w-full rounded-lg object-cover"
                                            />
                                            <button
                                                @click="removeBackgroundImage"
                                                type="button"
                                                class="absolute right-2 top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Templates -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Quick Templates
                                    </label>
                                    <div class="grid grid-cols-1 gap-1">
                                        <button
                                            v-for="template in quickTemplates"
                                            :key="template.name"
                                            type="button"
                                            @click="applyQuickTemplate(template)"
                                            class="px-3 py-2 text-xs font-medium text-left text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                                        >
                                            {{ template.name }} ({{ template.width }}×{{ template.height }})
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Center - Canvas Editor -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Canvas Editor</h2>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center justify-center">
                                    <div
                                        class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-crosshair"
                                        :style="{
                                            width: Math.min(form.width, 400) + 'px',
                                            height: Math.min(form.height, 300) + 'px',
                                            backgroundImage: backgroundImagePreview ? `url(${backgroundImagePreview})` : 'none',
                                            backgroundSize: 'cover',
                                            backgroundPosition: 'center',
                                            backgroundRepeat: 'no-repeat'
                                        }"
                                        @mousedown="handleMouseDown"
                                        @mousemove="handleMouseMove"
                                        @mouseup="handleMouseUp"
                                    >
                                        <!-- Canvas Elements -->
                                        <div
                                            v-for="element in sortedElements"
                                            :key="element.id"
                                            class="absolute cursor-move"
                                            :style="{
                                                left: element.x + 'px',
                                                top: element.y + 'px',
                                                width: element.width + 'px',
                                                height: element.height + 'px',
                                                zIndex: element.zIndex,
                                                transform: `rotate(${element.rotation}deg)`,
                                                filter: element.properties.boxShadow !== 'none' ? `drop-shadow(${element.properties.boxShadow})` : 'none'
                                            }"
                                            @mousedown="handleDragStart($event, element)"
                                            @click="selectElement(element)"
                                        >
                                            <!-- Text Element -->
                                            <div v-if="element.type === 'text'" class="w-full h-full flex items-center justify-center" :style="{
                                                color: element.properties.color,
                                                backgroundColor: element.properties.backgroundColor,
                                                fontSize: element.properties.fontSize + 'px',
                                                fontFamily: element.properties.fontFamily,
                                                fontWeight: element.properties.fontWeight,
                                                fontStyle: element.properties.fontStyle,
                                                textDecoration: element.properties.textDecoration,
                                                textAlign: element.properties.textAlign,
                                                lineHeight: element.properties.lineHeight,
                                                border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                                borderRadius: element.properties.borderRadius + 'px'
                                            }">
                                                {{ element.properties.text }}
                                            </div>

                                            <!-- Image Element -->
                                            <div v-else-if="element.type === 'image'" class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-600 rounded" :style="{
                                                border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                                borderRadius: element.properties.borderRadius + 'px'
                                            }">
                                                <img
                                                    v-if="element.properties.imageUrl"
                                                    :src="element.properties.imageUrl"
                                                    :alt="element.properties.imagePlaceholder"
                                                    class="w-full h-full object-cover rounded"
                                                    :style="{ objectFit: element.properties.imageFit }"
                                                />
                                                <span v-if="!element.properties.imageUrl" class="text-gray-500 text-sm">
                                                    {{ element.properties.imagePlaceholder }}
                                                </span>
                                            </div>

                                            <!-- Rectangle Element -->
                                            <div v-else-if="element.type === 'rectangle'" class="w-full h-full" :style="{
                                                backgroundColor: element.properties.fillColor,
                                                border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                                borderRadius: element.properties.borderRadius + 'px'
                                            }"></div>

                                            <!-- Circle Element -->
                                            <div v-else-if="element.type === 'circle'" class="w-full h-full rounded-full" :style="{
                                                backgroundColor: element.properties.fillColor,
                                                border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none'
                                            }"></div>

                                            <!-- Triangle Element -->
                                            <div v-else-if="element.type === 'triangle'" class="w-full h-full" :style="{
                                                backgroundColor: element.properties.fillColor,
                                                border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                                clipPath: 'polygon(50% 0%, 0% 100%, 100% 100%)'
                                            }"></div>

                                            <!-- Star Element -->
                                            <div v-else-if="element.type === 'star'" class="w-full h-full" :style="{
                                                backgroundColor: element.properties.fillColor,
                                                border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                                clipPath: 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)'
                                            }"></div>

                                            <!-- Heart Element -->
                                            <div v-else-if="element.type === 'heart'" class="w-full h-full" :style="{
                                                backgroundColor: element.properties.fillColor,
                                                border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                                clipPath: 'polygon(50% 25%, 20% 25%, 20% 45%, 50% 70%, 80% 45%, 80% 25%)'
                                            }"></div>

                                            <!-- Brush Element -->
                                            <div v-else-if="element.type === 'brush'" class="w-full h-full rounded-full" :style="{
                                                backgroundColor: element.properties.brushColor,
                                                opacity: element.properties.brushOpacity,
                                                width: element.properties.brushSize + 'px',
                                                height: element.properties.brushSize + 'px'
                                            }"></div>

                                            <!-- Resize Handle -->
                                            <div v-if="selectedElement?.id === element.id" class="absolute inset-0 pointer-events-none">
                                                <!-- Bottom-right corner resize handle -->
                                                <div
                                                    class="absolute w-4 h-4 bg-blue-500 border-2 border-white rounded-full cursor-se-resize pointer-events-auto"
                                                    style="bottom: -8px; right: -8px;"
                                                    @mousedown="handleResizeStart($event, element, 'se')"
                                                ></div>
                                            </div>
                                        </div>

                                        <!-- Canvas Info Overlay -->
                                        <div class="absolute top-2 left-2">
                                            <div class="bg-black bg-opacity-75 text-white px-2 py-1 rounded text-xs">
                                                {{ form.width }} × {{ form.height }}px
                                            </div>
                                        </div>

                                        <!-- Status Indicator -->
                                        <div class="absolute top-2 right-2">
                                            <div
                                                :class="backgroundImagePreview ? 'bg-green-500' : 'bg-gray-400'"
                                                class="w-2 h-2 rounded-full"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Canvas Info -->
                                <div class="mt-4 text-center">
                                    <div class="inline-flex items-center gap-4 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-900 dark:text-white">Elements:</span>
                                            <span class="text-gray-600 dark:text-gray-400 ml-1">{{ canvasElements.length }}</span>
                                        </div>
                                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-900 dark:text-white">Status:</span>
                                            <span
                                                :class="backgroundImagePreview ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'"
                                                class="ml-1"
                                            >
                                                {{ backgroundImagePreview ? 'Ready' : 'No background' }}
                                            </span>
                                        </div>
                                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-900 dark:text-white">Mode:</span>
                                            <span class="text-blue-600 dark:text-blue-400 ml-1">Edit</span>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Click on elements to select and edit them.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar - Tools & Properties -->
                    <div class="lg:col-span-1">
                        <div class="space-y-4">
                            <!-- Tools -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tools</h2>
                                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-3 gap-2">
                                        <button
                                            v-for="tool in tools"
                                            :key="tool.id"
                                            type="button"
                                            @click="selectedTool = tool.id"
                                            :class="[
                                                'flex flex-col items-center justify-center gap-1 px-2 py-3 rounded-lg text-xs font-medium transition-colors',
                                                selectedTool === tool.id
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                            ]"
                                        >
                                            <component :is="tool.icon" class="h-4 w-4" />
                                            <span class="text-xs">{{ tool.name }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Layers Panel -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Layers</h2>
                                </div>
                                <div class="p-4">
                                    <div class="space-y-2 max-h-48 overflow-y-auto">
                                        <div
                                            v-for="element in sortedElements.slice().reverse()"
                                            :key="element.id"
                                            class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                            :class="{ 'ring-2 ring-blue-500': selectedElement?.id === element.id }"
                                        >
                                            <div class="flex items-center gap-2">
                                                <component :is="tools.find(t => t.id === element.type)?.icon || Square" class="h-4 w-4 text-gray-500" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ element.type }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    @click="bringToFront(element.id)"
                                                    class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                                    title="Bring to Front"
                                                >
                                                    <Layers class="h-3 w-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteElement(element.id)"
                                                    class="p-1 text-red-500 hover:text-red-700"
                                                    title="Delete"
                                                >
                                                    <Trash class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </div>
                                        <div v-if="canvasElements.length === 0" class="text-center text-gray-500 dark:text-gray-400 text-sm py-4">
                                            No elements added yet
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Properties Panel -->
                            <div v-if="selectedElement" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Properties</h2>
                                </div>
                                <div class="p-4 space-y-4 overflow-y-auto max-h-96">
                                    <!-- Text Properties -->
                                    <div v-if="selectedElement.type === 'text'" class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Text Content
                                            </label>
                                            <textarea
                                                v-model="selectedElement.properties.text"
                                                rows="2"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors text-sm"
                                                placeholder="Enter text"
                                            ></textarea>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                    Font Size
                                                </label>
                                                <input
                                                    v-model.number="selectedElement.properties.fontSize"
                                                    type="number"
                                                    min="8"
                                                    max="72"
                                                    class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                    Font Family
                                                </label>
                                                <select
                                                    v-model="selectedElement.properties.fontFamily"
                                                    class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                                >
                                                    <option v-for="font in fontFamilies" :key="font" :value="font">{{ font }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-3 gap-1">
                                            <button
                                                type="button"
                                                @click="selectedElement.properties.fontWeight = selectedElement.properties.fontWeight === 'bold' ? 'normal' : 'bold'"
                                                :class="[
                                                    'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                    selectedElement.properties.fontWeight === 'bold' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                ]"
                                            >
                                                <Bold class="h-3 w-3" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="selectedElement.properties.fontStyle = selectedElement.properties.fontStyle === 'italic' ? 'normal' : 'italic'"
                                                :class="[
                                                    'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                    selectedElement.properties.fontStyle === 'italic' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                ]"
                                            >
                                                <Italic class="h-3 w-3" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="selectedElement.properties.textDecoration = selectedElement.properties.textDecoration === 'underline' ? 'none' : 'underline'"
                                                :class="[
                                                    'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                    selectedElement.properties.textDecoration === 'underline' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                ]"
                                            >
                                                <Underline class="h-3 w-3" />
                                            </button>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Text Align
                                            </label>
                                            <div class="grid grid-cols-3 gap-1">
                                                <button
                                                    type="button"
                                                    @click="selectedElement.properties.textAlign = 'left'"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                        selectedElement.properties.textAlign === 'left' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                    ]"
                                                >
                                                    <AlignLeft class="h-3 w-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="selectedElement.properties.textAlign = 'center'"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                        selectedElement.properties.textAlign === 'center' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                    ]"
                                                >
                                                    <AlignCenter class="h-3 w-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="selectedElement.properties.textAlign = 'right'"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                        selectedElement.properties.textAlign === 'right' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                    ]"
                                                >
                                                    <AlignRight class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Image Properties -->
                                    <div v-if="selectedElement.type === 'image'" class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Image URL
                                            </label>
                                            <input
                                                v-model="selectedElement.properties.imageUrl"
                                                type="url"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors text-sm"
                                                placeholder="Enter image URL"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Image Fit
                                            </label>
                                            <select
                                                v-model="selectedElement.properties.imageFit"
                                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            >
                                                <option value="cover">Cover</option>
                                                <option value="contain">Contain</option>
                                                <option value="fill">Fill</option>
                                                <option value="scale-down">Scale Down</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Brush Properties -->
                                    <div v-if="selectedElement.type === 'brush'" class="space-y-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Brush Size
                                            </label>
                                            <input
                                                v-model.number="selectedElement.properties.brushSize"
                                                type="range"
                                                min="1"
                                                max="50"
                                                class="w-full"
                                            />
                                            <span class="text-xs text-gray-500">{{ selectedElement.properties.brushSize }}px</span>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Brush Color
                                            </label>
                                            <input
                                                v-model="selectedElement.properties.brushColor"
                                                type="color"
                                                class="w-full h-8 border border-gray-300 dark:border-gray-600 rounded"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Opacity
                                            </label>
                                            <input
                                                v-model.number="selectedElement.properties.brushOpacity"
                                                type="range"
                                                min="0"
                                                max="1"
                                                step="0.1"
                                                class="w-full"
                                            />
                                            <span class="text-xs text-gray-500">{{ Math.round(selectedElement.properties.brushOpacity * 100) }}%</span>
                                        </div>
                                    </div>

                                    <!-- Colors -->
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Fill Color
                                            </label>
                                            <input
                                                v-model="selectedElement.properties.fillColor"
                                                type="color"
                                                class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700"
                                            />
                                            <!-- Color Palette -->
                                            <div class="mt-2">
                                                <div class="grid grid-cols-6 gap-1">
                                                    <button
                                                        v-for="color in colorPalette"
                                                        :key="color"
                                                        type="button"
                                                        @click="selectedElement.properties.fillColor = color"
                                                        class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                                                        :style="{ backgroundColor: color }"
                                                        :title="color"
                                                    ></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Stroke Color
                                            </label>
                                            <input
                                                v-model="selectedElement.properties.strokeColor"
                                                type="color"
                                                class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700"
                                            />
                                            <!-- Color Palette -->
                                            <div class="mt-2">
                                                <div class="grid grid-cols-6 gap-1">
                                                    <button
                                                        v-for="color in colorPalette"
                                                        :key="color"
                                                        type="button"
                                                        @click="selectedElement.properties.strokeColor = color"
                                                        class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                                                        :style="{ backgroundColor: color }"
                                                        :title="color"
                                                    ></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Border -->
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-2">
                                            <input
                                                v-model="selectedElement.properties.hasBorder"
                                                type="checkbox"
                                                id="hasBorder"
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            />
                                            <label for="hasBorder" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Enable Border
                                            </label>
                                        </div>

                                        <div v-if="selectedElement.properties.hasBorder" class="space-y-3">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                        Border Width
                                                    </label>
                                                    <input
                                                        v-model.number="selectedElement.properties.strokeWidth"
                                                        type="number"
                                                        step="0.5"
                                                        min="0"
                                                        max="20"
                                                        class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                        Border Style
                                                    </label>
                                                    <select
                                                        v-model="selectedElement.properties.borderStyle"
                                                        class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                                    >
                                                        <option v-for="style in borderStyles" :key="style.value" :value="style.value">{{ style.name }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                    Border Radius
                                                </label>
                                                <input
                                                    v-model.number="selectedElement.properties.borderRadius"
                                                    type="number"
                                                    step="0.5"
                                                    min="0"
                                                    max="50"
                                                    class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Shadow -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Shadow
                                        </label>
                                        <select
                                            v-model="selectedElement.properties.boxShadow"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                        >
                                            <option v-for="shadow in shadowPresets" :key="shadow.value" :value="shadow.value">{{ shadow.name }}</option>
                                        </select>
                                    </div>

                                    <!-- Position -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                X Position
                                            </label>
                                            <input
                                                v-model.number="selectedElement.x"
                                                type="number"
                                                step="0.5"
                                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Y Position
                                            </label>
                                            <input
                                                v-model.number="selectedElement.y"
                                                type="number"
                                                step="0.5"
                                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            />
                                        </div>
                                    </div>

                                    <!-- Size -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Width
                                            </label>
                                            <input
                                                v-model.number="selectedElement.width"
                                                type="number"
                                                step="0.5"
                                                min="10"
                                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Height
                                            </label>
                                            <input
                                                v-model.number="selectedElement.height"
                                                type="number"
                                                step="0.5"
                                                min="10"
                                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            />
                                        </div>
                                    </div>

                                    <!-- Layer Controls -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Layer</label>
                                        <div class="grid grid-cols-2 gap-1">
                                            <button
                                                type="button"
                                                @click="bringToFront(selectedElement.id)"
                                                class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                            >
                                                Bring to Front
                                            </button>
                                            <button
                                                type="button"
                                                @click="sendToBack(selectedElement.id)"
                                                class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                            >
                                                Send to Back
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1">
                                            <button
                                                type="button"
                                                @click="bringForward(selectedElement.id)"
                                                class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                            >
                                                Forward
                                            </button>
                                            <button
                                                type="button"
                                                @click="sendBackward(selectedElement.id)"
                                                class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                            >
                                                Backward
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Element Actions -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Actions</label>
                                        <div class="grid grid-cols-2 gap-1">
                                            <button
                                                type="button"
                                                @click="duplicateElement(selectedElement.id)"
                                                class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                            >
                                                Duplicate
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteElement(selectedElement.id)"
                                                class="px-2 py-1 text-xs font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 hover:bg-red-200 dark:hover:bg-red-800 rounded transition-colors"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
