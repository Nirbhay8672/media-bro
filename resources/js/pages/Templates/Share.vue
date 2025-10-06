<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, Type, Image, Upload } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import * as domtoimage from 'dom-to-image';
import Swal from 'sweetalert2';

interface Template {
    id: number;
    name: string;
    description?: string;
    width: number;
    height: number;
    background_image?: string;
    background_image_url?: string;
    canvas_data: any[];
    share_token: string;
    is_active: boolean;
}

interface CanvasElement {
    id: string;
    type: 'text' | 'image';
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
        backgroundColor?: string;

        
        hasBorder?: boolean;
        borderWidth?: number;
        borderColor?: string;
        borderStyle?: string;
        borderRadius?: number;

        
        boxShadow?: string;
        textShadow?: string;

        
        imageUrl?: string;
        imageFit?: string;
        imagePlaceholder?: string;
    };
}

const props = defineProps<{
    template: Template;
}>();


const selectedElement = ref<CanvasElement | null>(null);
const isGenerating = ref(false);


const canvasElements = ref<CanvasElement[]>([]);


const initializeCanvasElements = () => {
    
    let templateData;
    if (Array.isArray(props.template.canvas_data)) {
        templateData = props.template.canvas_data;
    } else if (typeof props.template.canvas_data === 'string') {
        try {
            templateData = JSON.parse(props.template.canvas_data);
        } catch (e) {
            templateData = [];
        }
    } else {
        templateData = [];
    }

    canvasElements.value = templateData.map((element: any) => ({
        ...element,
        properties: {
            text: element.properties?.text || '',
            fontSize: element.properties?.fontSize || 16,
            fontFamily: element.properties?.fontFamily || 'Arial',
            fontWeight: element.properties?.fontWeight || 'normal',
            fontStyle: element.properties?.fontStyle || 'normal',
            textDecoration: element.properties?.textDecoration || 'none',
            textAlign: element.properties?.textAlign || 'left',
            lineHeight: element.properties?.lineHeight || 1.2,

            
            color: element.properties?.color || '#000000',
            backgroundColor: element.properties?.backgroundColor || (element.type === 'text' ? 'transparent' : '#f3f4f6'),

            
            hasBorder: element.properties?.hasBorder || false,
            borderWidth: element.properties?.borderWidth || 1,
            borderColor: element.properties?.borderColor || '#000000',
            borderStyle: element.properties?.borderStyle || 'solid',
            borderRadius: element.properties?.borderRadius || 0,

            
            boxShadow: element.properties?.boxShadow || 'none',
            textShadow: element.properties?.textShadow || 'none',

            
            imageUrl: element.properties?.imageUrl || '',
            imageFit: element.properties?.imageFit || 'cover',
            imagePlaceholder: element.properties?.imagePlaceholder || (element.type === 'image' ? 'Click to upload image' : ''),
        }
    }));
};


initializeCanvasElements();


const selectElement = (element: CanvasElement) => {
    selectedElement.value = element;
};


const sortedElements = computed(() => {
    return [...canvasElements.value].sort((a, b) => a.zIndex - b.zIndex);
});

const textElements = computed(() => {
    return canvasElements.value.filter(el => el.type === 'text');
});

const imageElements = computed(() => {
    return canvasElements.value.filter(el => el.type === 'image');
});

// Drag functionality for text elements
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const draggedElement = ref<CanvasElement | null>(null);

const handleElementMouseDown = (event: MouseEvent, element: CanvasElement) => {
    if (element.type === 'text') {
        event.preventDefault();
        isDragging.value = true;
        dragStart.value = {
            x: event.clientX - element.x,
            y: event.clientY - element.y
        };
        draggedElement.value = element;
    }
};

const handleMouseMove = (event: MouseEvent) => {
    if (isDragging.value && draggedElement.value) {
        draggedElement.value.x = event.clientX - dragStart.value.x;
        draggedElement.value.y = event.clientY - dragStart.value.y;
    }
};

const handleMouseUp = (event: MouseEvent) => {
    isDragging.value = false;
    draggedElement.value = null;
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

const canvasStyle = computed(() => {
    // Calculate scale to fit within viewport while maintaining aspect ratio
    const maxWidth = 800;
    const maxHeight = 600;
    const scaleX = maxWidth / props.template.width;
    const scaleY = maxHeight / props.template.height;
    const scale = Math.min(scaleX, scaleY, 1); // Don't scale up
    
    return {
        width: props.template.width + 'px',
        height: props.template.height + 'px',
        transform: `scale(${scale})`,
        transformOrigin: 'top center',
        backgroundImage: props.template.background_image_url ? `url(${props.template.background_image_url})` : (props.template.background_image ? `url(/storage/${props.template.background_image})` : 'none'),
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat'
    };
});


const handleImageUpload = (event: Event, element: CanvasElement) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    
    if (!file.type.startsWith('image/')) {
        alert('Please select a valid image file.');
        return;
    }

    
    if (file.size > 10 * 1024 * 1024) {
        alert('File size must be less than 10MB.');
        return;
    }

    
    const imageUrl = URL.createObjectURL(file);
    element.properties.imageUrl = imageUrl;

    
    (element as any).uploadedFile = file;
};


const removeImage = (element: CanvasElement) => {
    
    if (element.properties.imageUrl && element.properties.imageUrl.startsWith('blob:')) {
        URL.revokeObjectURL(element.properties.imageUrl);
    }

    element.properties.imageUrl = '';
    (element as any).uploadedFile = null;
};





const generateImage = async () => {
    isGenerating.value = true;

    // Show loading notification
    Swal.fire({
        title: 'Generating Image...',
        text: 'Please wait while we create your image.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        // Find the preview container
        const previewElement = document.querySelector('.template-preview-container') as HTMLElement;
        if (!previewElement) {
            throw new Error('Preview container not found');
        }

        console.log('Preview element found:', previewElement);
        console.log('Element dimensions:', {
            width: previewElement.offsetWidth,
            height: previewElement.offsetHeight
        });

        // Ultra high quality rendering with multiple fallback methods
        let dataUrl;
        const originalWidth = props.template.width;
        const originalHeight = props.template.height;
        
        try {
            // Method 1: Ultra high quality with custom scaling
            const scaleFactor = 4; // 4x scale for ultra high quality
            const ultraWidth = originalWidth * scaleFactor;
            const ultraHeight = originalHeight * scaleFactor;
            
            dataUrl = await domtoimage.toPng(previewElement, {
                quality: 1.0,
                bgcolor: '#ffffff',
                width: ultraWidth,
                height: ultraHeight,
                style: {
                    transform: `scale(${scaleFactor})`,
                    transformOrigin: 'top left',
                    width: originalWidth + 'px',
                    height: originalHeight + 'px',
                    imageRendering: 'high-quality',
                    imageRendering: '-webkit-optimize-contrast',
                    imageRendering: 'crisp-edges'
                },
                pixelRatio: 4, // Ultra high DPI
                cacheBust: true,
                filter: (node) => {
                    // Include all elements for maximum detail
                    return true;
                },
                // Additional quality options
                useCORS: true,
                allowTaint: true
            });
            
            console.log('Ultra high quality image generated:', {
                originalSize: `${originalWidth}x${originalHeight}`,
                renderedSize: `${ultraWidth}x${ultraHeight}`,
                scaleFactor: scaleFactor,
                dataUrlLength: dataUrl.length
            });
            
        } catch (ultraError) {
            console.warn('Ultra quality method failed, trying high quality fallback:', ultraError);
            
            try {
                // Method 2: High quality fallback
                dataUrl = await domtoimage.toPng(previewElement, {
                    quality: 1.0,
                    bgcolor: '#ffffff',
                    width: originalWidth * 2, // 2x scale
                    height: originalHeight * 2,
                    style: {
                        transform: 'scale(2)',
                        transformOrigin: 'top left',
                        width: originalWidth + 'px',
                        height: originalHeight + 'px'
                    },
                    pixelRatio: 3,
                    cacheBust: true,
                    useCORS: true
                });
                
            } catch (highError) {
                console.warn('High quality method failed, trying standard quality:', highError);
                
                // Method 3: Standard quality as last resort
                dataUrl = await domtoimage.toPng(previewElement, {
                    quality: 1.0,
                    bgcolor: '#ffffff',
                    width: originalWidth,
                    height: originalHeight,
                    pixelRatio: 2,
                    cacheBust: true
                });
            }
        }

        console.log('Image generated successfully, data URL length:', dataUrl.length);

        // Create download link with timestamp
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
        const link = document.createElement('a');
        link.href = dataUrl;
        link.download = `${props.template.name}_${timestamp}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Track the download
        try {
            console.log('Tracking download for template:', props.template.share_token);
            const response = await fetch(`/template/${props.template.share_token}/track-download`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    file_name: `${props.template.name}_${timestamp}.png`,
                    file_size: dataUrl.length // Approximate file size
                })
            });
            
            if (response.ok) {
                console.log('Download tracked successfully');
            } else {
                console.error('Failed to track download:', response.status, response.statusText);
            }
    } catch (error) {
            console.error('Failed to track download:', error);
        }

        // Close loading and show success
        Swal.close();
        Swal.fire({
            icon: 'success',
            title: 'Download Complete!',
            text: 'Your image has been downloaded successfully.',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });

    } catch (error: any) {
        console.error('Error generating image:', error);
        
        // Close loading and show error
        Swal.close();
        
        // Provide more specific error messages
        let errorMessage = 'Unknown error occurred while generating image.';
        if (error.message) {
            errorMessage = error.message;
        } else if (error.name === 'SecurityError') {
            errorMessage = 'Security error: Cannot access the preview element. Please try refreshing the page.';
        } else if (error.name === 'InvalidStateError') {
            errorMessage = 'Invalid state error: The preview element may not be ready. Please try again.';
        } else if (error.toString().includes('canvas')) {
            errorMessage = 'Canvas rendering error: Please check if all images are loaded and try again.';
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Download Failed',
            text: errorMessage,
            confirmButtonText: 'Try Again'
        });
    } finally {
        isGenerating.value = false;
    }
};
</script>

<template>
    <Head :title="template.name" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Main Content -->
        <div class="mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-4">
                <!-- Left Sidebar - Content Upload -->
                <div class="lg:col-span-1">
                    <div class="space-y-4">
                        <div v-if="textElements.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Type class="h-5 w-5" />
                                    Text Content
                                </h2>
                            </div>
                            <div class="p-4 space-y-4">
                                <div
                                    v-for="(element, index) in textElements"
                                    :key="element.id"
                                    class="space-y-2"
                                >
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Text {{ index + 1 }}
                                    </label>
                                    <textarea
                                        v-model="element.properties.text"
                                        rows="2"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors text-sm"
                                        :placeholder="`Enter text ${index + 1}`"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div v-if="imageElements.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Image class="h-5 w-5" />
                                    Image Upload
                                </h2>
                            </div>
                            <div class="p-4 space-y-4">
                                <div
                                    v-for="(element, index) in imageElements"
                                    :key="element.id"
                                    class="space-y-2"
                                >
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Image {{ index + 1 }}
                                    </label>
                                    <div class="space-y-2">
                                        <!-- File Upload Input -->
                                        <input
                                            :id="`image-${element.id}`"
                                            type="file"
                                            accept="image/*"
                                            @change="handleImageUpload($event, element)"
                                            class="hidden"
                                        />
                                        <label
                                            :for="`image-${element.id}`"
                                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600"
                                        >
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <Upload class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" />
                                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF up to 10MB</p>
                                            </div>
                                        </label>

                                        <!-- Image Preview -->
                                        <div v-if="element.properties.imageUrl" class="mt-2">
                                            <img
                                                :src="element.properties.imageUrl"
                                                :alt="`Image ${index + 1}`"
                                                class="w-full h-24 object-cover rounded-lg border border-gray-300 dark:border-gray-600"
                                            />
                                            <button
                                                type="button"
                                                @click="removeImage(element)"
                                                class="mt-1 text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                Remove image
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No Content Message -->
                        <div v-if="textElements.length === 0 && imageElements.length === 0" class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                            <div class="p-4">
                                <h3 class="text-sm font-medium text-yellow-900 dark:text-yellow-100 mb-2">
                                    No content fields found
                                </h3>
                                <p class="text-xs text-yellow-800 dark:text-yellow-200">
                                    This template doesn't have any text or image placeholders yet. The template creator needs to add content fields first.
                                </p>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <div class="p-4">
                                <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                                    How to use this template:
                                </h3>
                                <ul class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                                    <li>• Fill in the text fields with your content</li>
                                    <li>• Upload images by clicking the upload areas</li>
                                    <li>• Click "Download Image" to generate your final image</li>
                                    <li>• All positioning and styling is already set by the template creator</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Center - Template Preview -->
                <div class="lg:col-span-3">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ template.name }}</h2>
                            <button
                                @click="generateImage"
                                :disabled="isGenerating"
                                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                <Download class="h-4 w-4" />
                                {{ isGenerating ? 'Generating...' : 'Download Image' }}
                            </button>
                        </div>
                        <div class="p-4">

                            <div class="flex justify-center items-start min-h-[400px]">
                                <div class="relative" style="margin: 20px 0;">
                                    <div
                                        class="template-preview-container relative overflow-hidden bg-gray-50 dark:bg-gray-700 shadow-lg"
                                        :style="canvasStyle"
                                        @mousemove="handleMouseMove"
                                        @mouseup="handleMouseUp"
                                >
                                    <!-- Canvas Elements -->
                                    <div
                                        v-for="element in sortedElements"
                                        :key="element.id"
                                        class="absolute"
                                        :style="{
                                            left: element.x + 'px',
                                            top: element.y + 'px',
                                            width: element.width + 'px',
                                            height: element.height + 'px',
                                            zIndex: element.zIndex,
                                            transform: `rotate(${element.rotation}deg)`,
                                            filter: element.properties.boxShadow !== 'none' ? `drop-shadow(${element.properties.boxShadow})` : 'none'
                                        }"
                                    >
                                        <!-- Text Element -->
                                        <div 
                                            v-if="element.type === 'text'" 
                                            class="w-full h-full flex items-center justify-center cursor-move select-none" 
                                            :style="{
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
                                            }"
                                            @mousedown="handleElementMouseDown($event, element)"
                                        >
                                            {{ element.properties.text || 'Your text here' }}
                                        </div>

                                        <!-- Image Element -->
                                        <div v-else-if="element.type === 'image'" class="w-full h-full flex items-center justify-center" :style="{
                                            backgroundColor: element.properties.backgroundColor || 'transparent',
                                            border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                            borderRadius: element.properties.borderRadius + 'px',
                                            clipPath: getImageClipPath(element.properties.imageShape || 'rectangle')
                                        }">
                                            <img
                                                v-if="element.properties.imageUrl"
                                                :src="element.properties.imageUrl"
                                                :alt="element.properties.imagePlaceholder"
                                                class="max-w-full max-h-full"
                                                :style="{ objectFit: element.properties.imageFit || 'contain' }"
                                            />
                                            <div v-else class="flex flex-col items-center justify-center text-gray-500 text-sm text-center p-2">
                                                <Upload class="h-8 w-8 mb-2" />
                                                <span>{{ element.properties.imagePlaceholder }}</span>
                                            </div>
                                        </div>

                                        <!-- Rectangle Element -->
                                        <div v-else-if="element.type === 'rectangle'" class="w-full h-full" :style="{
                                            backgroundColor: element.properties.fillColor || '#ffffff',
                                            border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                            borderRadius: (element.properties.borderRadius || 0) + 'px'
                                        }"></div>

                                        <!-- Circle Element -->
                                        <div v-else-if="element.type === 'circle'" class="w-full h-full rounded-full" :style="{
                                            backgroundColor: element.properties.fillColor || '#ffffff',
                                            border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none'
                                        }"></div>

                                        <!-- Triangle Element -->
                                        <div v-else-if="element.type === 'triangle'" class="w-full h-full" :style="{
                                            backgroundColor: element.properties.fillColor || '#ffffff',
                                            border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                            clipPath: 'polygon(50% 0%, 0% 100%, 100% 100%)'
                                        }"></div>

                                        <!-- Star Element -->
                                        <div v-else-if="element.type === 'star'" class="w-full h-full" :style="{
                                            backgroundColor: element.properties.fillColor || '#ffffff',
                                            border: element.properties.hasBorder ? `${element.properties.strokeWidth}px ${element.properties.borderStyle} ${element.properties.strokeColor}` : 'none',
                                            clipPath: 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)'
                                        }"></div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
