<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import templates from '@/routes/templates';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Save, Upload, Type, Image, Square, Circle, Move, RotateCcw, RotateCw, Trash2, Triangle, Star, Heart, Palette, Layers, AlignLeft, AlignCenter, AlignRight, Bold, Italic, Underline, Copy, ChevronDown } from 'lucide-vue-next';
import { ref, reactive, computed, watch } from 'vue';

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

// Tool options
const tools = [
    { id: 'select', name: 'Select', icon: Move },
    { id: 'text', name: 'Text', icon: Type },
    { id: 'image', name: 'Image', icon: Image },
    { id: 'rectangle', name: 'Rectangle', icon: Square },
    { id: 'circle', name: 'Circle', icon: Circle },
    { id: 'triangle', name: 'Triangle', icon: Triangle },
    { id: 'star', name: 'Star', icon: Star },
    { id: 'heart', name: 'Heart', icon: Heart },
];

// Font families
const fontFamilies = [
    'Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana', 'Courier New',
    'Impact', 'Comic Sans MS', 'Trebuchet MS', 'Arial Black', 'Palatino',
    'Garamond', 'Bookman', 'Avant Garde', 'Helvetica Neue', 'Roboto',
    'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Source Sans Pro'
];

// Shadow presets
const shadowPresets = [
    { name: 'None', value: 'none' },
    { name: 'Small', value: 'drop-shadow(0 2px 4px rgba(0,0,0,0.1))' },
    { name: 'Medium', value: 'drop-shadow(0 4px 8px rgba(0,0,0,0.15))' },
    { name: 'Large', value: 'drop-shadow(0 8px 16px rgba(0,0,0,0.2))' },
    { name: 'X-Large', value: 'drop-shadow(0 12px 24px rgba(0,0,0,0.25))' },
    { name: 'Glow', value: 'drop-shadow(0 0 20px rgba(0,0,0,0.3))' }
];

// Border styles
const borderStyles = [
    { name: 'Solid', value: 'solid' },
    { name: 'Dashed', value: 'dashed' },
    { name: 'Dotted', value: 'dotted' },
    { name: 'Double', value: 'double' },
    { name: 'Groove', value: 'groove' },
    { name: 'Ridge', value: 'ridge' },
    { name: 'Inset', value: 'inset' },
    { name: 'Outset', value: 'outset' }
];

// Color palette
const colorPalette = [
    '#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00',
    '#FF00FF', '#00FFFF', '#FFA500', '#800080', '#FFC0CB', '#A52A2A',
    '#808080', '#C0C0C0', '#FFD700', '#008000', '#000080', '#800000'
];

// Quick templates
const quickTemplates = [
    { name: 'Social Media Post', width: 1080, height: 1080 },
    { name: 'Instagram Story', width: 1080, height: 1920 },
    { name: 'Facebook Cover', width: 1200, height: 630 },
    { name: 'Twitter Header', width: 1500, height: 500 },
    { name: 'YouTube Thumbnail', width: 1280, height: 720 },
    { name: 'Business Card', width: 1050, height: 600 },
    { name: 'A4 Document', width: 2480, height: 3508 },
    { name: 'Custom', width: 800, height: 600 }
];

// Element types
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
    };
}

// Create new element
const createElement = (type: string) => {
    const maxZIndex = Math.max(...canvasElements.value.map(el => el.zIndex), 0);

    const element: CanvasElement = {
        id: `element_${Date.now()}`,
        type: type as any,
        x: 100,
        y: 100,
        width: type === 'text' ? 200 : 100,
        height: type === 'text' ? 50 : 100,
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
            backgroundColor: type === 'text' ? 'transparent' : (type === 'image' ? 'transparent' : '#000000'),

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
const updateElementProperty = (elementId: string, property: keyof CanvasElement['properties'], value: any) => {
    const elementIndex = canvasElements.value.findIndex(el => el.id === elementId);
    if (elementIndex !== -1) {
        const element = canvasElements.value[elementIndex];
        element.properties[property] = value;
        
        // Force reactivity update
        canvasElements.value[elementIndex] = { ...element };
        
        // Update selected element if it's the same
        if (selectedElement.value?.id === elementId) {
            selectedElement.value = { ...element };
        }
    }
};

// Update selected element property directly
const updateSelectedElementProperty = (property: keyof CanvasElement['properties'], value: any) => {
    if (selectedElement.value) {
        selectedElement.value.properties[property] = value;
        updateElementProperty(selectedElement.value.id, property, value);
    }
};

// Helper function to get input value safely
const getInputValue = (event: Event): string => {
    const target = event.target as HTMLInputElement;
    return target?.value || '';
};

// Helper function to get checkbox checked state safely
const getCheckboxChecked = (event: Event): boolean => {
    const target = event.target as HTMLInputElement;
    return target?.checked || false;
};

// Layer management
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
        const newElement = {
            ...element,
            id: `element_${Date.now()}`,
            x: element.x + 20,
            y: element.y + 20,
            zIndex: Math.max(...canvasElements.value.map(el => el.zIndex)) + 1
        };
        canvasElements.value.push(newElement);
        selectedElement.value = newElement;
    }
};

// Sort elements by z-index
const sortedElements = computed(() => {
    return [...canvasElements.value].sort((a, b) => a.zIndex - b.zIndex);
});

// Apply quick template
const applyQuickTemplate = (template: any) => {
    selectedQuickTemplate.value = template;
    if (template.name !== 'Custom') {
        form.value.width = template.width;
        form.value.height = template.height;
    }
    isQuickTemplateDropdownOpen.value = false;
};

// Toggle dropdown
const toggleQuickTemplateDropdown = () => {
    isQuickTemplateDropdownOpen.value = !isQuickTemplateDropdownOpen.value;
};

// Close dropdown when clicking outside
const closeDropdownOnOutsideClick = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.quick-template-dropdown')) {
        isQuickTemplateDropdownOpen.value = false;
    }
};

// Handle keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && isQuickTemplateDropdownOpen.value) {
        isQuickTemplateDropdownOpen.value = false;
    }
};

// Watch for changes in selected element properties and update the element
watch(selectedElement, (newElement) => {
    if (newElement) {
        // Force reactivity by creating a new reference
        const elementIndex = canvasElements.value.findIndex(el => el.id === newElement.id);
        if (elementIndex !== -1) {
            canvasElements.value[elementIndex] = { ...newElement };
        }
    }
}, { deep: true });

// Canvas interactions
const handleCanvasClick = (event: MouseEvent) => {
        if (selectedTool.value !== 'select') {
            if (selectedTool.value === 'text') {
                createElement('text');
            } else if (selectedTool.value === 'rectangle') {
                createElement('rectangle');
            } else if (selectedTool.value === 'circle') {
                createElement('circle');
            } else if (selectedTool.value === 'triangle') {
                createElement('triangle');
            } else if (selectedTool.value === 'star') {
                createElement('star');
            } else if (selectedTool.value === 'heart') {
                createElement('heart');
            } else if (selectedTool.value === 'image') {
                createElement('image');
            }
        }
    };

const handleElementMouseDown = (event: MouseEvent, element: CanvasElement) => {
        selectedElement.value = element;
        if (selectedTool.value === 'select') {
            isDragging.value = true;
            dragStart.value = { x: event.clientX - element.x, y: event.clientY - element.y };
        }
};

const handleMouseMove = (event: MouseEvent) => {
    if (isDragging.value && selectedElement.value) {
        selectedElement.value.x = event.clientX - dragStart.value.x;
        selectedElement.value.y = event.clientY - dragStart.value.y;
    }
    handleResizeMove(event);
};

const handleMouseUp = () => {
    isDragging.value = false;
    isResizing.value = false;
    resizeHandle.value = '';
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
const canvasStyle = computed(() => ({
    width: Math.min(form.value.width, 600) + 'px',
    height: Math.min(form.value.height, 400) + 'px',
    backgroundImage: backgroundImagePreview.value ? `url(${backgroundImagePreview.value})` : 'none',
    backgroundSize: 'cover',
    backgroundPosition: 'center',
}));

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

const submitForm = () => {
    // Prevent double submission
    if (isSubmitting.value) {
        return;
    }

    // Validate required fields
    if (!form.value.name.trim()) {
        alert('Please enter a template name');
        return;
    }

    if (form.value.width < 100 || form.value.height < 100) {
        alert('Canvas dimensions must be at least 100x100 pixels');
        return;
    }

    // Confirm submission
    if (!confirm('Are you sure you want to create this template? This will save all elements to the database.')) {
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

    router.post(templates.store(), formData, {
        forceFormData: true,
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <Head title="Create Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900" @click="closeDropdownOnOutsideClick" @keydown="handleKeydown">
            <!-- Main Content -->
            <div class="w-full px-4 py-6">
                <form @submit.prevent="submitForm" class="flex h-[calc(100vh-200px)] gap-4">
                    <!-- Left Sidebar - Template Settings -->
                    <div class="w-80 flex-shrink-0">
                        <div class="h-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Template Settings</h2>
                            </div>
                            <div class="p-4 space-y-4 overflow-y-auto">
                                <!-- Template Name -->
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

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors resize-none text-sm"
                                        placeholder="Describe your template"
                                    ></textarea>
                                </div>

                                    <!-- Quick Templates -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Quick Templates
                                        </label>
                                        <div class="relative quick-template-dropdown">
                                            <button
                                                type="button"
                                                @click="toggleQuickTemplateDropdown"
                                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            >
                                                <span>
                                                    {{ selectedQuickTemplate ? selectedQuickTemplate.name : 'Select a template...' }}
                                                    <span v-if="selectedQuickTemplate" class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                                        ({{ selectedQuickTemplate.width }} × {{ selectedQuickTemplate.height }}px)
                                                    </span>
                                                </span>
                                                <ChevronDown 
                                                    :class="[
                                                        'h-4 w-4 transition-transform',
                                                        isQuickTemplateDropdownOpen ? 'rotate-180' : ''
                                                    ]"
                                                />
                                            </button>
                                            
                                            <!-- Dropdown Menu -->
                                            <div
                                                v-if="isQuickTemplateDropdownOpen"
                                                class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
                                            >
                                            <button
                                                v-for="template in quickTemplates"
                                                :key="template.name"
                                                type="button"
                                                @click="applyQuickTemplate(template)"
                                                    :class="[
                                                        'w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors',
                                                        selectedQuickTemplate?.name === template.name 
                                                            ? 'bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300' 
                                                            : 'text-gray-700 dark:text-gray-300'
                                                    ]"
                                                >
                                                    <div class="font-medium">{{ template.name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ template.width }} × {{ template.height }}px
                                                    </div>
                                            </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dimensions -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Canvas Size
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                    Width
                                                </label>
                                                <input
                                                    v-model.number="form.width"
                                                    type="number"
                                                    min="100"
                                                    max="4000"
                                                    required
                                                    class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                    Height
                                                </label>
                                                <input
                                                    v-model.number="form.height"
                                                    type="number"
                                                    min="100"
                                                    max="4000"
                                                    required
                                                    class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                <!-- Background Image -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Background
                                    </label>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-center w-full">
                                            <label class="flex flex-col items-center justify-center w-full h-20 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500">
                                                <div class="flex flex-col items-center justify-center pt-3 pb-3">
                                                    <Upload class="w-4 h-4 mb-1 text-gray-500 dark:text-gray-400" />
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Upload Image</p>
                                                </div>
                                                <input
                                                    @change="handleBackgroundImage"
                                                    type="file"
                                                    accept="image/*"
                                                    class="hidden"
                                                />
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div class="space-y-3">
                                        <button
                                            type="submit"
                                            :disabled="isSubmitting"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <Save class="h-4 w-4" />
                                            {{ isSubmitting ? 'Creating Template...' : 'Create Template' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Center - Canvas Editor -->
                    <div class="flex-1 min-w-0">
                        <div class="h-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Template Editor</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Click on tools to add elements, then drag to position them</p>
                            </div>

                                <!-- Quick Actions Toolbar -->
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                @click="selectedElement && bringToFront(selectedElement.id)"
                                                :disabled="!selectedElement"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <Layers class="h-3 w-3" />
                                                Bring to Front
                                            </button>
                                            <button
                                                type="button"
                                                @click="selectedElement && sendToBack(selectedElement.id)"
                                                :disabled="!selectedElement"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <Layers class="h-3 w-3 rotate-180" />
                                                Send to Back
                                            </button>
                                            <button
                                                type="button"
                                                @click="selectedElement && duplicateElement(selectedElement.id)"
                                                :disabled="!selectedElement"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <Copy class="h-3 w-3" />
                                                Duplicate
                                            </button>
                                            <button
                                                type="button"
                                                @click="selectedElement && deleteElement(selectedElement.id)"
                                                :disabled="!selectedElement"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                Delete
                                            </button>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ canvasElements.length }} element{{ canvasElements.length !== 1 ? 's' : '' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 h-[calc(100%-140px)] overflow-auto">
                                    <div class="flex items-center justify-center min-h-full">
                                    <div class="relative">
                                        <!-- Canvas -->
                                        <div
                                            class="relative overflow-hidden border-2 border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700 shadow-lg rounded-lg cursor-crosshair"
                                            :style="canvasStyle"
                                            @click="handleCanvasClick"
                                            @mousemove="handleMouseMove"
                                            @mouseup="handleMouseUp"
                                        >
                                            <!-- Background Image -->
                                            <div v-if="backgroundImagePreview" class="absolute inset-0 bg-cover bg-center bg-no-repeat" :style="{ backgroundImage: `url(${backgroundImagePreview})` }"></div>

                                            <!-- Empty State -->
                                            <div v-if="!backgroundImagePreview && canvasElements.length === 0" class="flex h-full w-full items-center justify-center">
                                                <div class="text-center">
                                                    <div class="mx-auto h-16 w-16 bg-gray-100 dark:bg-gray-600 rounded-full flex items-center justify-center mb-4">
                                                        <Upload class="h-8 w-8 text-gray-400" />
                                                    </div>
                                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Start Creating</h3>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        Select a tool and click on the canvas to add elements
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Canvas Elements -->
                                            <div
                                                v-for="element in sortedElements"
                                                :key="element.id"
                                                :class="[
                                                    'absolute cursor-move select-none',
                                                    selectedElement?.id === element.id ? 'ring-2 ring-blue-500' : ''
                                                ]"
                                                :style="{
                                                    left: element.x + 'px',
                                                    top: element.y + 'px',
                                                    width: element.width + 'px',
                                                    height: element.height + 'px',
                                                    transform: `rotate(${element.rotation}deg)`,
                                                    zIndex: element.zIndex,
                                                    filter: element.properties.boxShadow !== 'none' ? element.properties.boxShadow : 'none',
                                                    textShadow: element.properties.textShadow
                                                }"
                                                @mousedown="handleElementMouseDown($event, element)"
                                                @click.stop="selectElement(element)"
                                            >
                                                <!-- Text Element -->
                                                <div v-if="element.type === 'text'" class="w-full h-full flex items-center justify-center text-center" :style="{
                                                    fontSize: (element.properties.fontSize || 16) + 'px',
                                                    fontFamily: element.properties.fontFamily || 'Arial',
                                                    fontWeight: element.properties.fontWeight || 'normal',
                                                    fontStyle: element.properties.fontStyle || 'normal',
                                                    textDecoration: element.properties.textDecoration || 'none',
                                                    textAlign: element.properties.textAlign || 'left',
                                                    lineHeight: String(element.properties.lineHeight || 1.2),
                                                    color: element.properties.color || '#000000',
                                                    backgroundColor: element.properties.backgroundColor || 'transparent',
                                                    border: `${element.properties.borderWidth || 0}px ${element.properties.borderStyle || 'solid'} ${element.properties.borderColor || '#000000'}`,
                                                    borderRadius: (element.properties.borderRadius || 0) + 'px'
                                                } as any">
                                                    {{ element.properties.text }}
                                                </div>

                                                <!-- Image Element -->
                                                <div v-else-if="element.type === 'image'" class="w-full h-full flex items-center justify-center text-center" :style="{
                                                    backgroundColor: element.properties.backgroundColor || 'transparent',
                                                    border: `${element.properties.borderWidth || 0}px ${element.properties.borderStyle || 'solid'} ${element.properties.borderColor || '#000000'}`,
                                                    borderRadius: (element.properties.borderRadius || 0) + 'px',
                                                    backgroundImage: element.properties.imageUrl ? `url(${element.properties.imageUrl})` : 'none',
                                                    backgroundSize: element.properties.imageFit || 'cover',
                                                    backgroundPosition: 'center',
                                                    backgroundRepeat: 'no-repeat'
                                                }">
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
                                                        <span class="text-blue-600 dark:text-blue-400 ml-1">Draft</span>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                    Elements are in draft mode. They will be saved when you create the template.
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar - Tools & Properties -->
                    <div class="w-80 flex-shrink-0">
                        <div class="h-full space-y-4">
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
                                <div class="p-4 max-h-48 overflow-y-auto">
                                    <div v-if="canvasElements.length === 0" class="text-center text-gray-500 dark:text-gray-400 text-sm py-4">
                                        No elements yet
                                    </div>
                                    <div v-else class="space-y-1">
                                        <div
                                            v-for="(element, index) in sortedElements.slice().reverse()"
                                            :key="element.id"
                                            @click="selectElement(element)"
                                            :class="[
                                                'flex items-center gap-2 p-2 rounded cursor-pointer transition-colors',
                                                selectedElement?.id === element.id
                                                    ? 'bg-blue-100 dark:bg-blue-900'
                                                    : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                                            ]"
                                        >
                                            <div class="w-4 h-4 rounded border" :style="{ backgroundColor: element.properties.fillColor || element.properties.color }"></div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {{ element.type.charAt(0).toUpperCase() + element.type.slice(1) }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Layer {{ sortedElements.length - index }}
                                                </div>
                                            </div>
                                            <div class="flex gap-1">
                                                <button
                                                    type="button"
                                                    @click.stop="bringForward(element.id)"
                                                    class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                                                    title="Bring Forward"
                                                >
                                                    <Layers class="h-3 w-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    @click.stop="sendBackward(element.id)"
                                                    class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                                                    title="Send Backward"
                                                >
                                                    <Layers class="h-3 w-3 rotate-180" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Element Properties -->
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
                                                :value="selectedElement.properties.text"
                                                @input="updateSelectedElementProperty('text', getInputValue($event))"
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
                                                    :value="selectedElement.properties.fontSize"
                                                    @input="updateSelectedElementProperty('fontSize', parseInt(getInputValue($event)) || 16)"
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
                                                    :value="selectedElement.properties.fontFamily"
                                                    @change="updateSelectedElementProperty('fontFamily', getInputValue($event))"
                                                    class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                                >
                                                    <option v-for="font in fontFamilies" :key="font" :value="font">{{ font }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-3 gap-1">
                                            <button
                                                type="button"
                                                @click="updateSelectedElementProperty('fontWeight', selectedElement.properties.fontWeight === 'bold' ? 'normal' : 'bold')"
                                                :class="[
                                                    'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                    selectedElement.properties.fontWeight === 'bold' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                ]"
                                            >
                                                <Bold class="h-3 w-3" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="updateSelectedElementProperty('fontStyle', selectedElement.properties.fontStyle === 'italic' ? 'normal' : 'italic')"
                                                :class="[
                                                    'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                    selectedElement.properties.fontStyle === 'italic' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                ]"
                                            >
                                                <Italic class="h-3 w-3" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="updateSelectedElementProperty('textDecoration', selectedElement.properties.textDecoration === 'underline' ? 'none' : 'underline')"
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
                                                    @click="updateSelectedElementProperty('textAlign', 'left')"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                        selectedElement.properties.textAlign === 'left' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                    ]"
                                                >
                                                    <AlignLeft class="h-3 w-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="updateSelectedElementProperty('textAlign', 'center')"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-colors',
                                                        selectedElement.properties.textAlign === 'center' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                    ]"
                                                >
                                                    <AlignCenter class="h-3 w-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="updateSelectedElementProperty('textAlign', 'right')"
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
                                                :value="selectedElement.properties.imageUrl"
                                                @input="updateSelectedElementProperty('imageUrl', getInputValue($event))"
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
                                                :value="selectedElement.properties.imageFit"
                                                @change="updateSelectedElementProperty('imageFit', getInputValue($event))"
                                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            >
                                                <option value="cover">Cover</option>
                                                <option value="contain">Contain</option>
                                                <option value="fill">Fill</option>
                                                <option value="scale-down">Scale Down</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Background
                                            </label>
                                            <div class="space-y-2">
                                                <div class="flex items-center space-x-2">
                                            <input
                                                        :checked="selectedElement.properties.backgroundColor === 'transparent'"
                                                        @change="updateSelectedElementProperty('backgroundColor', 'transparent')"
                                                        type="radio"
                                                        id="transparent-bg"
                                                        name="image-bg"
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                                    />
                                                    <label for="transparent-bg" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Transparent
                                            </label>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                            <input
                                                        :checked="selectedElement.properties.backgroundColor !== 'transparent'"
                                                        @change="updateSelectedElementProperty('backgroundColor', '#ffffff')"
                                                        type="radio"
                                                        id="color-bg"
                                                        name="image-bg"
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                                    />
                                                    <label for="color-bg" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Background Color
                                            </label>
                                                </div>
                                                <div v-if="selectedElement.properties.backgroundColor !== 'transparent'" class="ml-6">
                                            <input
                                                        :value="selectedElement.properties.backgroundColor"
                                                        @input="updateSelectedElementProperty('backgroundColor', getInputValue($event))"
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
                                                                @click="updateSelectedElementProperty('backgroundColor', color)"
                                                                class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                                                                :style="{ backgroundColor: color }"
                                                                :title="color"
                                                            ></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- Colors -->
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Fill Color
                                            </label>
                                            <input
                                                :value="selectedElement.properties.fillColor"
                                                @input="updateSelectedElementProperty('fillColor', getInputValue($event))"
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
                                                        @click="updateSelectedElementProperty('fillColor', color)"
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
                                                :value="selectedElement.properties.strokeColor"
                                                @input="updateSelectedElementProperty('strokeColor', getInputValue($event))"
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
                                                        @click="updateSelectedElementProperty('strokeColor', color)"
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
                                                :checked="selectedElement.properties.hasBorder"
                                                @change="updateSelectedElementProperty('hasBorder', getCheckboxChecked($event))"
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
                                                        :value="selectedElement.properties.strokeWidth"
                                                        @input="updateSelectedElementProperty('strokeWidth', parseFloat(getInputValue($event)) || 0)"
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
                                                        :value="selectedElement.properties.borderStyle"
                                                        @change="updateSelectedElementProperty('borderStyle', getInputValue($event))"
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
                                                    :value="selectedElement.properties.borderRadius"
                                                    @input="updateSelectedElementProperty('borderRadius', parseFloat(getInputValue($event)) || 0)"
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
                                            :value="selectedElement.properties.boxShadow"
                                            @change="updateSelectedElementProperty('boxShadow', getInputValue($event))"
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
                                                :value="selectedElement.x"
                                                @input="selectedElement.x = parseFloat(getInputValue($event)) || 0"
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
                                                :value="selectedElement.y"
                                                @input="selectedElement.y = parseFloat(getInputValue($event)) || 0"
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
                                                :value="selectedElement.width"
                                                @input="selectedElement.width = parseFloat(getInputValue($event)) || 10"
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
                                                :value="selectedElement.height"
                                                @input="selectedElement.height = parseFloat(getInputValue($event)) || 10"
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
                                                class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                            >
                                                <Layers class="h-3 w-3 mx-auto" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="sendToBack(selectedElement.id)"
                                                class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                            >
                                                <Layers class="h-3 w-3 mx-auto rotate-180" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="space-y-2">
                                        <button
                                            type="button"
                                            @click="duplicateElement(selectedElement.id)"
                                            class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
                                        >
                                            <Copy class="h-4 w-4" />
                                            Duplicate
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteElement(selectedElement.id)"
                                            class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            Delete Element
                                        </button>
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
