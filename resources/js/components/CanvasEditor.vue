<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
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
    (e: 'backgroundImageChange', file: File): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Handle background image change
const handleBackgroundImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        emit('backgroundImageChange', file);
    }
};

// Window width for responsive design
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

// Background drag and zoom functionality
const backgroundTransform = ref({ x: 0, y: 0, scale: 1 });
const isDraggingBackground = ref(false);
const isZoomingBackground = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const lastTouchDistance = ref(0);

// Update window width on resize
const updateWindowWidth = () => {
    windowWidth.value = window.innerWidth;
};

onMounted(() => {
    window.addEventListener('resize', updateWindowWidth);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateWindowWidth);
});

// Watch for canvas size changes and reset background position
watch(() => [props.form.width, props.form.height], () => {
    autoFitBackground();
}, { deep: true });

// Calculate responsive canvas dimensions
const canvasDimensions = computed(() => {
    const maxWidth = windowWidth.value < 1024 ? 400 : 600;
    const maxHeight = windowWidth.value < 1024 ? 300 : 450;
    
    // Calculate scale to fit within max dimensions while maintaining aspect ratio
    const scaleX = maxWidth / props.form.width;
    const scaleY = maxHeight / props.form.height;
    const scale = Math.min(scaleX, scaleY, 1); // Never scale up beyond original size
    
    return {
        width: props.form.width * scale,
        height: props.form.height * scale,
        scale: scale
    };
});

// Calculate canvas scale factor - now only responsive, no manual zoom
const canvasScale = computed(() => {
    return canvasDimensions.value.scale;
});

// Canvas style is now handled by the container div

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
    
    // Get the actual canvas scale (responsive scale only)
    const actualScale = canvasDimensions.value.scale;
    
    // Adjust deltas for canvas scaling
    const scaledDeltaX = deltaX / actualScale;
    const scaledDeltaY = deltaY / actualScale;
    
    let newWidth = Math.max(20, resizeStart.value.width + scaledDeltaX);
    let newHeight = Math.max(20, resizeStart.value.height + scaledDeltaY);
    
    // Check if Shift is pressed for proportional resizing
    const isShiftPressed = event.shiftKey;
    
    if (isShiftPressed) {
        // Proportional resizing - maintain aspect ratio
        const aspectRatio = resizeStart.value.width / resizeStart.value.height;
        const maxDelta = Math.max(scaledDeltaX, scaledDeltaY);
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

// Background drag and zoom handlers
const handleBackgroundMouseDown = (event: MouseEvent) => {
    // Only allow background dragging if no element is selected
    if (props.backgroundImagePreview && !props.selectedElement) {
        event.preventDefault();
        isDraggingBackground.value = true;
        dragStart.value = {
            x: event.clientX - backgroundTransform.value.x,
            y: event.clientY - backgroundTransform.value.y
        };
    }
};

const handleBackgroundMouseMove = (event: MouseEvent) => {
    if (isDraggingBackground.value && props.backgroundImagePreview && !props.selectedElement) {
        backgroundTransform.value.x = event.clientX - dragStart.value.x;
        backgroundTransform.value.y = event.clientY - dragStart.value.y;
    }
};

const handleBackgroundMouseUp = () => {
    isDraggingBackground.value = false;
};

const handleBackgroundWheel = (event: WheelEvent) => {
    // Only allow background zooming if no element is selected
    if (props.backgroundImagePreview && !props.selectedElement) {
        event.preventDefault();
        const delta = event.deltaY > 0 ? 0.9 : 1.1;
        const newScale = Math.max(0.1, Math.min(5, backgroundTransform.value.scale * delta));
        
        // Zoom towards mouse position
        const rect = (event.target as HTMLElement).getBoundingClientRect();
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;
        
        const scaleChange = newScale / backgroundTransform.value.scale;
        backgroundTransform.value.x = mouseX - (mouseX - backgroundTransform.value.x) * scaleChange;
        backgroundTransform.value.y = mouseY - (mouseY - backgroundTransform.value.y) * scaleChange;
        backgroundTransform.value.scale = newScale;
    }
};

// Touch handlers for mobile
const handleBackgroundTouchStart = (event: TouchEvent) => {
    // Only allow background touch interactions if no element is selected
    if (props.backgroundImagePreview && !props.selectedElement) {
        event.preventDefault();
        if (event.touches.length === 1) {
            isDraggingBackground.value = true;
            dragStart.value = {
                x: event.touches[0].clientX - backgroundTransform.value.x,
                y: event.touches[0].clientY - backgroundTransform.value.y
            };
        } else if (event.touches.length === 2) {
            isZoomingBackground.value = true;
            const touch1 = event.touches[0];
            const touch2 = event.touches[1];
            lastTouchDistance.value = Math.sqrt(
                Math.pow(touch2.clientX - touch1.clientX, 2) + 
                Math.pow(touch2.clientY - touch1.clientY, 2)
            );
        }
    }
};

const handleBackgroundTouchMove = (event: TouchEvent) => {
    if (props.backgroundImagePreview && !props.selectedElement) {
        event.preventDefault();
        if (isDraggingBackground.value && event.touches.length === 1) {
            backgroundTransform.value.x = event.touches[0].clientX - dragStart.value.x;
            backgroundTransform.value.y = event.touches[0].clientY - dragStart.value.y;
        } else if (isZoomingBackground.value && event.touches.length === 2) {
            const touch1 = event.touches[0];
            const touch2 = event.touches[1];
            const currentDistance = Math.sqrt(
                Math.pow(touch2.clientX - touch1.clientX, 2) + 
                Math.pow(touch2.clientY - touch1.clientY, 2)
            );
            
            if (lastTouchDistance.value > 0) {
                const scaleChange = currentDistance / lastTouchDistance.value;
                const newScale = Math.max(0.1, Math.min(5, backgroundTransform.value.scale * scaleChange));
                
                // Zoom towards center of touches
                const centerX = (touch1.clientX + touch2.clientX) / 2;
                const centerY = (touch1.clientY + touch2.clientY) / 2;
                const rect = (event.target as HTMLElement).getBoundingClientRect();
                const mouseX = centerX - rect.left;
                const mouseY = centerY - rect.top;
                
                const scaleRatio = newScale / backgroundTransform.value.scale;
                backgroundTransform.value.x = mouseX - (mouseX - backgroundTransform.value.x) * scaleRatio;
                backgroundTransform.value.y = mouseY - (mouseY - backgroundTransform.value.y) * scaleRatio;
                backgroundTransform.value.scale = newScale;
            }
            
            lastTouchDistance.value = currentDistance;
        }
    }
};

const handleBackgroundTouchEnd = () => {
    isDraggingBackground.value = false;
    isZoomingBackground.value = false;
    lastTouchDistance.value = 0;
};

// Reset background transform
const resetBackgroundTransform = () => {
    backgroundTransform.value = { x: 0, y: 0, scale: 1 };
};

// Auto-fit background to canvas when canvas size changes
const autoFitBackground = () => {
    if (props.backgroundImagePreview) {
        // Reset position and scale to center the image
        backgroundTransform.value = { x: 0, y: 0, scale: 1 };
    }
};

// Fit background to canvas (show full image)
const fitBackgroundToCanvas = () => {
    if (props.backgroundImagePreview) {
        // Get the image dimensions from the background image
        const img = new Image();
        img.onload = () => {
            const canvasAspect = props.form.width / props.form.height;
            const imageAspect = img.naturalWidth / img.naturalHeight;
            
            // Calculate scale to fit the image within the canvas
            let scale = 1;
            if (imageAspect > canvasAspect) {
                // Image is wider than canvas - scale by width
                scale = props.form.width / img.naturalWidth;
            } else {
                // Image is taller than canvas - scale by height
                scale = props.form.height / img.naturalHeight;
            }
            
            // Center the image
            const scaledWidth = img.naturalWidth * scale;
            const scaledHeight = img.naturalHeight * scale;
            const x = (props.form.width - scaledWidth) / 2;
            const y = (props.form.height - scaledHeight) / 2;
            
            backgroundTransform.value = { x, y, scale };
        };
        img.src = props.backgroundImagePreview;
    }
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
    <div class="flex-1 min-w-0 relative">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col relative">
            <!-- Canvas Editor Header -->
            <div class="pt-5 px-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Template Editor</h2>
            </div>

            <!-- Background Selection and Actions -->
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <!-- Background Upload -->
                    <div class="flex items-center gap-3">
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleBackgroundImageChange"
                            class="hidden"
                            id="background-upload"
                        />
                        <label for="background-upload" class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <Upload class="h-3 w-3" />
                            Background
                        </label>
                        <div v-if="backgroundImagePreview" class="flex items-center gap-2">
                            <img :src="backgroundImagePreview" alt="Background preview" class="h-6 w-6 object-cover rounded" />
                            <span class="text-xs text-gray-500 dark:text-gray-400">Background set - Drag to move, scroll to zoom, use buttons to fit/reset</span>
                        </div>
                    </div>
                    

                    <!-- Vertical Action Buttons -->
                    <div class="flex items-center gap-1">
                        <button
                            v-if="backgroundImagePreview"
                            type="button"
                            @click="fitBackgroundToCanvas"
                            title="Fit Background to Canvas (Show Full Image)"
                            class="p-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                            </svg>
                        </button>
                        <button
                            v-if="backgroundImagePreview"
                            type="button"
                            @click="resetBackgroundTransform"
                            title="Reset Background Position & Zoom"
                            class="p-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="selectedElement && bringToFront(selectedElement.id)"
                            :disabled="!selectedElement"
                            title="Bring to Front"
                            class="p-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <Layers class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click="selectedElement && sendToBack(selectedElement.id)"
                            :disabled="!selectedElement"
                            title="Send to Back"
                            class="p-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <Layers class="h-4 w-4 rotate-180" />
                        </button>
                        <button
                            type="button"
                            @click="selectedElement && duplicateElement(selectedElement.id)"
                            :disabled="!selectedElement"
                            title="Duplicate"
                            class="p-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <Copy class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click="selectedElement && deleteElement(selectedElement.id)"
                            :disabled="!selectedElement"
                            title="Delete"
                            class="p-2 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-2 sm:p-4 flex justify-center items-start min-h-[300px] sm:min-h-[400px]">
                <div class="relative w-full max-w-full overflow-hidden flex justify-center" style="margin: 10px 0;">
                        <!-- Canvas Container -->
                        <div class="canvas-container" :style="{ 
                            width: canvasDimensions.width + 'px', 
                            height: canvasDimensions.height + 'px',
                            transform: `scale(${canvasScale})`,
                            transformOrigin: 'top center',
                            margin: '0 auto'
                        }">
                            <!-- Canvas -->
                            <div
                                class="relative overflow-hidden bg-gray-50 dark:bg-gray-700 shadow-lg cursor-crosshair border-2 border-black"
                                :style="{
                                    width: props.form.width + 'px',
                                    height: props.form.height + 'px'
                                }"
                                @click="handleCanvasClick"
                                @mousemove="handleMouseMove"
                                @mouseup="handleMouseUp"
                            >
                            <!-- Background Image -->
                            <div 
                                v-if="backgroundImagePreview" 
                                class="absolute inset-0 bg-contain bg-center bg-no-repeat z-0" 
                                :class="selectedElement ? 'cursor-default' : 'cursor-move'"
                                :style="{ 
                                    backgroundImage: `url(${backgroundImagePreview})`,
                                    width: '100%',
                                    height: '100%',
                                    transform: `translate(${backgroundTransform.x}px, ${backgroundTransform.y}px) scale(${backgroundTransform.scale})`,
                                    transformOrigin: '0 0'
                                }"
                                @mousedown="handleBackgroundMouseDown"
                                @mousemove="handleBackgroundMouseMove"
                                @mouseup="handleBackgroundMouseUp"
                                @wheel="handleBackgroundWheel"
                                @touchstart="handleBackgroundTouchStart"
                                @touchmove="handleBackgroundTouchMove"
                                @touchend="handleBackgroundTouchEnd"
                            ></div>
                            

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
                                    selectedElement?.id === element.id ? 'border-2 border-blue-500' : ''
                                ]"
                                :style="{
                                    left: (selectedElement?.id === element.id ? element.x - 2 : element.x) + 'px',
                                    top: (selectedElement?.id === element.id ? element.y - 2 : element.y) + 'px',
                                    width: (selectedElement?.id === element.id ? element.width + 4 : element.width) + 'px',
                                    height: (selectedElement?.id === element.id ? element.height + 4 : element.height) + 'px',
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
                                        :style="{ objectFit: element.properties.imageFit || 'cover' } as any"
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
                        </div> <!-- Close canvas container -->
                    </div>
                </div>
            </div>
            
            <!-- Slot for Tools & Properties -->
            <slot />
        </div>
</template>

<style scoped>
</style>
