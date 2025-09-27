<script setup lang="ts">
import { computed , ref } from 'vue';
import { Upload, Layers, Copy, Trash2 } from 'lucide-vue-next';

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

interface Props {
    form: {
        width: number;
        height: number;
    };
    backgroundImagePreview: string | null;
    canvasElements: CanvasElement[];
    selectedElement: CanvasElement | null;
    selectedTool: string;
}

interface Emits {
    (e: 'canvasClick', event: MouseEvent): void;
    (e: 'elementMouseDown', event: MouseEvent, element: CanvasElement): void;
    (e: 'mouseMove', event: MouseEvent): void;
    (e: 'mouseUp', event: MouseEvent): void;
    (e: 'selectElement', element: CanvasElement): void;
    (e: 'bringToFront', elementId: string): void;
    (e: 'sendToBack', elementId: string): void;
    (e: 'duplicateElement', elementId: string): void;
    (e: 'deleteElement', elementId: string): void;
    (e: 'resizeElement', elementId: string, width: number, height: number, x: number, y: number, fontSize?: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const canvasStyle = computed(() => ({
    width: Math.min(props.form.width, 600) + 'px',
    height: Math.min(props.form.height, 400) + 'px',
    backgroundImage: props.backgroundImagePreview ? `url(${props.backgroundImagePreview})` : 'none',
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    backgroundRepeat: 'no-repeat'
}));

const sortedElements = computed(() => {
    return [...props.canvasElements].sort((a, b) => a.zIndex - b.zIndex);
});

const handleCanvasClick = (event: MouseEvent) => {
    emit('canvasClick', event);
};

const handleElementMouseDown = (event: MouseEvent, element: CanvasElement) => {
    emit('elementMouseDown', event, element);
};

const handleMouseMove = (event: MouseEvent) => {
    if (isResizing.value) {
        handleResize(event);
    } else {
        emit('mouseMove', event);
    }
};

const handleMouseUp = (event: MouseEvent) => {
    if (isResizing.value) {
        handleResizeEnd();
    } else {
        emit('mouseUp', event);
    }
};

const selectElement = (element: CanvasElement) => {
    emit('selectElement', element);
};

const handlePlaceholderClick = (element: CanvasElement) => {
    // Select the element when placeholder is clicked
    selectElement(element);
    // You could also emit a special event here if you want to open an image picker
    // emit('openImagePicker', element.id);
};

const bringToFront = (elementId: string) => {
    emit('bringToFront', elementId);
};

const sendToBack = (elementId: string) => {
    emit('sendToBack', elementId);
};

const duplicateElement = (elementId: string) => {
    emit('duplicateElement', elementId);
};

const deleteElement = (elementId: string) => {
    emit('deleteElement', elementId);
};

// Resize functionality - single handle at bottom right
const isResizing = ref(false);
const resizeStart = ref({ x: 0, y: 0, width: 0, height: 0, elementX: 0, elementY: 0, originalFontSize: 0 });

const handleResizeStart = (event: MouseEvent, element: CanvasElement) => {
    event.preventDefault();
    event.stopPropagation();
    
    isResizing.value = true;
    resizeStart.value = {
        x: event.clientX,
        y: event.clientY,
        width: element.width,
        height: element.height,
        elementX: element.x,
        elementY: element.y,
        originalFontSize: element.properties.fontSize || 16
    };
    
    // Select the element if it's not already selected
    if (props.selectedElement?.id !== element.id) {
        selectElement(element);
    }
};

const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !props.selectedElement) return;
    
    const deltaX = event.clientX - resizeStart.value.x;
    const deltaY = event.clientY - resizeStart.value.y;
    
    let newWidth = Math.max(20, resizeStart.value.width + deltaX);
    let newHeight = Math.max(20, resizeStart.value.height + deltaY);
    
    // Check if Shift is pressed for proportional resizing
    const isShiftPressed = event.shiftKey;
    
    if (isShiftPressed) {
        // Proportional resizing - maintain aspect ratio
        const aspectRatio = resizeStart.value.width / resizeStart.value.height;
        const maxDelta = Math.max(deltaX, deltaY);
        newWidth = Math.max(20, resizeStart.value.width + maxDelta);
        newHeight = Math.max(20, resizeStart.value.height + maxDelta);
    }
    
    // Calculate font size for text elements
    let newFontSize = resizeStart.value.originalFontSize;
    if (props.selectedElement.type === 'text') {
        const heightRatio = newHeight / resizeStart.value.height;
        newFontSize = Math.max(8, Math.round(resizeStart.value.originalFontSize * heightRatio));
    }
    
    // Emit resize event
    emit('resizeElement', props.selectedElement.id, newWidth, newHeight, props.selectedElement.x, props.selectedElement.y, newFontSize);
};

const handleResizeEnd = () => {
    isResizing.value = false;
};

// Image shape clip paths
const getImageClipPath = (shape: string) => {
    switch (shape) {
        case 'circle':
            return 'circle(50% at 50% 50%)';
        case 'triangle':
            return 'polygon(50% 0%, 0% 100%, 100% 100%)';
        case 'star':
            return 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)';
        case 'diamond':
            return 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)';
        case 'hexagon':
            return 'polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)';
        case 'octagon':
            return 'polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%)';
        case 'rectangle':
        default:
            return 'none';
    }
};

</script>

<template>
    <div class="flex-1 min-w-0">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <!-- Canvas Editor Header -->
            <div class="pt-5 px-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Template Editor</h2>
            </div>

            <!-- Quick Actions Toolbar -->
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
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

            <div class="p-4">
                <div class="flex justify-center w-full">
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
                                    color: element.properties.color || '#000000'
                                } as any">
                                    {{ element.properties.text || 'Text' }}
                                </div>

                                <!-- Image Element -->
                                <div v-else-if="element.type === 'image'" class="w-full h-full flex items-center justify-center text-center" :style="{
                                    backgroundColor: element.properties.backgroundColor || 'transparent',
                                    border: `${element.properties.borderWidth || 0}px ${element.properties.borderStyle || 'solid'} ${element.properties.borderColor || '#000000'}`,
                                    borderRadius: (element.properties.borderRadius || 0) + 'px',
                                    clipPath: getImageClipPath(element.properties.imageShape || 'rectangle')
                                }">
                                    <img
                                        v-if="element.properties.imageUrl"
                                        :src="element.properties.imageUrl"
                                        :alt="element.properties.text || 'Image'"
                                        class="max-w-full max-h-full"
                                        :style="{ objectFit: element.properties.imageFit || 'contain' } as any"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-blue-500 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 border-2 border-dashed border-blue-300 dark:border-blue-600 rounded-lg cursor-pointer hover:from-blue-100 hover:to-indigo-200 dark:hover:from-blue-800/30 dark:hover:to-indigo-800/30 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-200" @click="handlePlaceholderClick(element)">
                                        <div class="relative">
                                            <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rectangle Element -->
                                <div v-else-if="element.type === 'rectangle'" class="w-full h-full" :style="{
                                    backgroundColor: element.properties.fillColor,
                                    border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                    borderRadius: (element.properties.borderRadius || 0) + 'px'
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


                                <!-- Single Resize Handle (only show when selected) -->
                                <div v-if="selectedElement?.id === element.id" class="absolute -bottom-1 -right-1 w-4 h-4 bg-blue-500 border-2 border-white rounded-full pointer-events-auto cursor-se-resize hover:bg-blue-600 transition-colors shadow-lg" @mousedown="handleResizeStart($event, element)" title="Resize (Shift+drag for proportional)"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-4">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Elements:</span>
                            <span class="text-blue-600 dark:text-blue-400 ml-1">{{ canvasElements.length }}</span>
                        </div>
                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">Status:</span>
                            <span class="text-green-600 dark:text-green-400 ml-1">
                                {{ backgroundImagePreview ? 'Ready' : 'No background' }}
                            </span>
                        </div>
                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">Mode:</span>
                            <span class="text-blue-600 dark:text-blue-400 ml-1">Draft</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Elements are in draft mode. They will be saved when you create the template.
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
