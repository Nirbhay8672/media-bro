<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { X, Plus, GripVertical } from 'lucide-vue-next';

// PDF.js types
declare global {
    interface Window {
        pdfjsLib: any;
    }
}

interface Field {
    id: string;
    x: number;
    y: number;
    width: number;
    height: number;
    column: string;
    label: string;
    fontSize: number;
    textAlign: 'left' | 'center' | 'right';
    fontFamily?: string;
    fontColor?: string;
    fontWeight?: 'normal' | 'bold';
    fontStyle?: 'normal' | 'italic';
    textDecoration?: 'none' | 'underline';
}

interface Props {
    template: {
        name: string;
        width: number;
        height: number;
        fields: Field[];
    };
    showOnlyCanvas?: boolean;
    selectedFieldId?: string | null;
    pdfBackgroundUrl?: string;
    currentPageNumber?: number; // Current page number to display (1-based)
}

const props = defineProps<Props>();
const emit = defineEmits<{
    update: [template: Props['template']];
    fieldSelected: [field: Field | null];
    pdfImageConverted: [base64: string | string[]]; // Can be single image or array of images for multiple pages
    pdfPagesDetected: [numPages: number]; // Emit when PDF pages are detected
}>();

const canvasRef = ref<HTMLElement | null>(null);
const selectedField = ref<Field | null>(null);
const pdfCanvasRef = ref<HTMLCanvasElement | null>(null);
const pdfLoading = ref(false);
const pdfError = ref<string | null>(null);
const pdfDocument = ref<any>(null); // Store the PDF document
const pdfLoadingTask = ref<any>(null); // Store the loading task
const cachedPageImages = ref<Map<number, HTMLImageElement>>(new Map()); // Cache all page images
const cachedPageCanvases = ref<Map<number, HTMLCanvasElement>>(new Map()); // Cache all page canvases

// Watch for external selection changes
watch(() => props.selectedFieldId, (newId) => {
    if (newId) {
        const field = fields.value.find(f => f.id === newId);
        if (field) {
            selectedField.value = field;
        }
    } else {
        selectedField.value = null;
    }
}, { immediate: true });

const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const isResizing = ref(false);
const resizeHandle = ref<string | null>(null);
const showFieldDialog = ref(false);
const fieldForm = ref<Partial<Field>>({
    label: '',
    column: '',
    fontSize: 16,
    textAlign: 'left',
    fontFamily: 'Arial',
    fontColor: '#000000',
    fontWeight: 'normal',
    fontStyle: 'normal',
    textDecoration: 'none',
});

// Reset form when dialog opens
watch(showFieldDialog, (isOpen) => {
    if (isOpen) {
        fieldForm.value = {
            label: '',
            column: '',
            fontSize: 16,
            textAlign: 'left',
            fontFamily: 'Arial',
            fontColor: '#000000',
            fontWeight: 'normal',
            fontStyle: 'normal',
            textDecoration: 'none',
        };
    }
});

// Convert mm to pixels for display (1mm = 3.779527559 pixels at 96 DPI)
const mmToPx = 3.779527559;

const scale = computed(() => {
    if (!canvasRef.value) return 1;
    // Get container dimensions
    const container = canvasRef.value.parentElement;
    if (!container) return 1;
    
    const containerWidth = container.clientWidth - 32; // Account for padding (8px * 4 = 32px)
    const containerHeight = container.clientHeight - 32; // Account for padding
    
    // Convert template dimensions from mm to pixels
    const templateWidthPx = props.template.width * mmToPx;
    const templateHeightPx = props.template.height * mmToPx;
    
    // Calculate scale based on height to fill screen height while maintaining aspect ratio
    const heightScale = containerHeight / templateHeightPx;
    const widthScale = containerWidth / templateWidthPx;
    
    // Use height scale primarily to fill screen height, but ensure it fits width too
    // This ensures canvas fills screen height while maintaining A4 aspect ratio
    return Math.min(heightScale, widthScale) * 0.98;
});

const scaledWidth = computed(() => props.template.width * mmToPx * scale.value);
const scaledHeight = computed(() => props.template.height * mmToPx * scale.value);

const fields = computed(() => props.template.fields);

const updateTemplate = () => {
    emit('update', {
        ...props.template,
        fields: [...fields.value],
    });
};

const canAddField = computed(() => {
    return fieldForm.value.label?.trim() && fieldForm.value.column?.trim();
});

const addField = () => {
    if (!canAddField.value) return;
    
    const newField: Field = {
        id: Date.now().toString(),
        x: 10,
        y: 10,
        width: 100,
        height: 30,
        column: fieldForm.value.column?.trim() || '',
        label: fieldForm.value.label?.trim() || '',
        fontSize: fieldForm.value.fontSize || 16,
        textAlign: fieldForm.value.textAlign || 'left',
        fontFamily: fieldForm.value.fontFamily || 'Arial',
        fontColor: fieldForm.value.fontColor || '#000000',
        fontWeight: fieldForm.value.fontWeight || 'normal',
        fontStyle: fieldForm.value.fontStyle || 'normal',
        textDecoration: fieldForm.value.textDecoration || 'none',
    };
    
    fields.value.push(newField);
    updateTemplate();
    showFieldDialog.value = false;
    fieldForm.value = { 
        label: '', 
        column: '', 
        fontSize: 16, 
        textAlign: 'left',
        fontFamily: 'Arial',
        fontColor: '#000000',
        fontWeight: 'normal',
        fontStyle: 'normal',
        textDecoration: 'none',
    };
    selectedField.value = newField;
};

const deleteField = (fieldId: string) => {
    const index = fields.value.findIndex(f => f.id === fieldId);
    if (index > -1) {
        fields.value.splice(index, 1);
        updateTemplate();
        if (selectedField.value?.id === fieldId) {
            selectedField.value = null;
            emit('fieldSelected', null);
        }
    }
};

const selectField = (field: Field) => {
    selectedField.value = field;
    emit('fieldSelected', field);
};

const getFieldStyle = (field: Field) => {
    // Convert mm to pixels for display (1mm = 3.779527559 pixels at 96 DPI)
    const mmToPx = 3.779527559;
    const xPx = field.x * mmToPx * scale.value;
    const yPx = field.y * mmToPx * scale.value;
    const widthPx = field.width * mmToPx * scale.value;
    
    // Use 'auto' height by default, but use fixed height if it was explicitly set via resize
    // If height is 0 or undefined, use 'auto', otherwise use the calculated height
    const heightStyle = (field.height && field.height > 0) 
        ? `${field.height * mmToPx * scale.value}px` 
        : 'auto';
    
    return {
        left: `${xPx}px`,
        top: `${yPx}px`,
        width: `${widthPx}px`,
        height: heightStyle,
        fontFamily: field.fontFamily || 'Arial',
        color: field.fontColor || '#000000',
        fontWeight: field.fontWeight || 'normal',
        fontStyle: field.fontStyle || 'normal',
        textDecoration: field.textDecoration || 'none',
        fontSize: `${(field.fontSize || 16) * scale.value}px`,
        textAlign: field.textAlign || 'left',
    };
};

const startDrag = (e: MouseEvent, field: Field) => {
    if ((e.target as HTMLElement).classList.contains('resize-handle')) return;
    if ((e.target as HTMLElement).tagName === 'BUTTON') return;
    
    e.preventDefault();
    e.stopPropagation();
    
    isDragging.value = true;
    selectedField.value = field;
    
    if (!canvasRef.value) return;
    const rect = canvasRef.value.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;
    
    // Convert pixel position to mm (accounting for scale)
    const mmToPx = 3.779527559;
    const x = (mouseX / scale.value) / mmToPx;
    const y = (mouseY / scale.value) / mmToPx;
    
    dragOffset.value = {
        x: x - field.x,
        y: y - field.y,
    };
};

const onMouseMove = (e: MouseEvent) => {
    if (!canvasRef.value) return;
    
    const rect = canvasRef.value.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;
    
    // Convert pixel position to mm (accounting for scale)
    const mmToPx = 3.779527559;
    const x = (mouseX / scale.value) / mmToPx;
    const y = (mouseY / scale.value) / mmToPx;

    if (isDragging.value && selectedField.value) {
        const field = selectedField.value;
        const newX = x - dragOffset.value.x;
        const newY = y - dragOffset.value.y;
        
        // Use minimum height of 20px for drag calculations if height is auto (0)
        const effectiveHeight = field.height > 0 ? field.height : 20;
        field.x = Math.max(0, Math.min(newX, props.template.width - field.width));
        field.y = Math.max(0, Math.min(newY, props.template.height - effectiveHeight));
        updateTemplate();
    } else if (isResizing.value && selectedField.value && resizeHandle.value) {
        const field = selectedField.value;
        const handle = resizeHandle.value;
        
        if (handle.includes('e')) {
            field.width = Math.max(20, Math.min(x - field.x, props.template.width - field.x));
        }
        if (handle.includes('s')) {
            field.height = Math.max(20, Math.min(y - field.y, props.template.height - field.y));
        }
        if (handle.includes('w')) {
            const newWidth = field.width + (field.x - x);
            if (newWidth >= 20 && x >= 0) {
                field.width = newWidth;
                field.x = x;
            }
        }
        if (handle.includes('n')) {
            const newHeight = field.height + (field.y - y);
            if (newHeight >= 20 && y >= 0) {
                field.height = newHeight;
                field.y = y;
            }
        }
        updateTemplate();
    }
};

const startResize = (e: MouseEvent, handle: string) => {
    e.stopPropagation();
    isResizing.value = true;
    resizeHandle.value = handle;
    if (selectedField.value) {
        const field = selectedField.value;
        dragOffset.value = {
            x: field.x * scale.value,
            y: field.y * scale.value,
        };
    }
};

// Load and render PDF
const loadPdf = async () => {
    if (!props.pdfBackgroundUrl) {
        return;
    }
    
    if (!pdfCanvasRef.value) {
        return;
    }
    
    pdfLoading.value = true;
    pdfError.value = null;
    
    try {
        // Load PDF.js from CDN if not already loaded
        if (!window.pdfjsLib) {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                script.onload = () => {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    resolve(true);
                };
                script.onerror = () => {
                    reject(new Error('Failed to load PDF.js'));
                };
                document.head.appendChild(script);
            });
        }
        await renderPdf();
    } catch (error: any) {
        pdfError.value = 'Failed to load PDF: ' + (error.message || 'Unknown error');
        pdfLoading.value = false;
    }
};

const renderPdf = async () => {
    if (!props.pdfBackgroundUrl || !pdfCanvasRef.value || !window.pdfjsLib) {
        return;
    }
    
    try {
        // Use stored PDF document if available, otherwise load it
        let pdf = pdfDocument.value;
        if (!pdf || !pdfLoadingTask.value) {
            // Create new loading task
            const loadingTask = window.pdfjsLib.getDocument({
                url: props.pdfBackgroundUrl,
                withCredentials: false
            });
            pdfLoadingTask.value = loadingTask;
            pdf = await loadingTask.promise;
            pdfDocument.value = pdf; // Store for future use
            
            // Emit the number of pages detected when PDF is first loaded
            emit('pdfPagesDetected', pdf.numPages);
        }
        
        // Verify PDF document is still valid
        if (!pdf) {
            throw new Error('PDF document is not loaded');
        }
        
        // Ensure PDF is fully loaded by checking numPages
        if (!pdf.numPages || pdf.numPages === 0) {
            throw new Error('PDF document is not fully loaded');
        }
        
        // Get the current page to display (default to page 1)
        const pageNumberToDisplay = props.currentPageNumber || 1;
        const pageNumber = Math.min(Math.max(1, pageNumberToDisplay), pdf.numPages);
        
        // Get the page with error handling
        let page;
        try {
            page = await pdf.getPage(pageNumber);
        } catch (pageError: any) {
            // If page access fails, reload the PDF from scratch
            pdfDocument.value = null;
            pdfLoadingTask.value = null;
            
            const loadingTask = window.pdfjsLib.getDocument({
                url: props.pdfBackgroundUrl,
                withCredentials: false
            });
            pdfLoadingTask.value = loadingTask;
            pdf = await loadingTask.promise;
            pdfDocument.value = pdf;
            
            // Try getting the page again
            page = await pdf.getPage(pageNumber);
        }
        const viewport = page.getViewport({ scale: 1.0 });
        
        const canvas = pdfCanvasRef.value;
        const context = canvas.getContext('2d');
        
        if (!context) {
            throw new Error('Could not get canvas context');
        }
        
        // Set canvas size to match template dimensions (in pixels)
        const mmToPx = 3.779527559;
        const templateWidthPx = props.template.width * mmToPx * scale.value;
        const templateHeightPx = props.template.height * mmToPx * scale.value;
        
        // Calculate scale to fit PDF in canvas while maintaining aspect ratio
        const scaleX = templateWidthPx / viewport.width;
        const scaleY = templateHeightPx / viewport.height;
        const fitScale = Math.min(scaleX, scaleY);
        
        const scaledViewport = page.getViewport({ scale: fitScale });
        
        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;
        
        const renderContext = {
            canvasContext: context,
            viewport: scaledViewport
        };
        
        await page.render(renderContext).promise;
        
        // Convert all pages to images for export
        const exportScale = 2.5;
        const pageImages: string[] = [];
        
        // Process all pages
        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            const exportPage = await pdf.getPage(pageNum);
            const exportViewport = exportPage.getViewport({ scale: exportScale });
            
            // Create temporary canvas for export
            const exportCanvas = document.createElement('canvas');
            exportCanvas.width = exportViewport.width;
            exportCanvas.height = exportViewport.height;
            
            const exportContext = exportCanvas.getContext('2d', {
                alpha: false,
                desynchronized: false,
                willReadFrequently: false
            });
            
            if (!exportContext) {
                throw new Error('Could not get export canvas context');
            }
            
            // Fill white background for proper alignment
            exportContext.fillStyle = '#FFFFFF';
            exportContext.fillRect(0, 0, exportCanvas.width, exportCanvas.height);
            
            // Center the PDF content when using cover mode
            const exportOffsetX = (exportCanvas.width - exportViewport.width) / 2;
            const exportOffsetY = (exportCanvas.height - exportViewport.height) / 2;
            
            exportContext.save();
            exportContext.translate(exportOffsetX, exportOffsetY);
            
            // Enable high-quality rendering
            exportContext.imageSmoothingEnabled = true;
            exportContext.imageSmoothingQuality = 'high';
            
            const exportRenderContext = {
                canvasContext: exportContext,
                viewport: exportViewport
            };
            
            await exportPage.render(exportRenderContext).promise;
            exportContext.restore();
            
            // Use JPG format with optimized quality for faster processing
            const imageData = exportCanvas.toDataURL('image/jpeg', 0.85);
            pageImages.push(imageData);
        }
        
        // Emit single image for first page (backward compatibility) or array for all pages
        if (pageImages.length === 1) {
            emit('pdfImageConverted', pageImages[0]);
        } else {
            emit('pdfImageConverted', pageImages);
        }
        
        pdfLoading.value = false;
    } catch (error: any) {
        pdfError.value = 'Failed to render PDF: ' + (error.message || 'Unknown error');
        pdfLoading.value = false;
    }
};

// Watch for page number changes to re-render the correct page
watch(() => props.currentPageNumber, async () => {
    if (pdfCanvasRef.value && props.pdfBackgroundUrl) {
        // Ensure PDF is loaded before rendering
        if (!pdfDocument.value) {
            await loadPdf();
        } else {
            await renderPdf();
        }
    }
});

// Watch for PDF URL changes
watch(() => props.pdfBackgroundUrl, (newUrl) => {
    // Reset PDF document and loading task when URL changes
    pdfDocument.value = null;
    pdfLoadingTask.value = null;
    if (newUrl) {
        // Wait for next tick to ensure canvas is rendered
        setTimeout(() => {
            if (pdfCanvasRef.value) {
                loadPdf();
            }
        }, 200);
    }
}, { immediate: true });

// Watch for scale changes to re-render PDF
watch(scale, () => {
    if (props.pdfBackgroundUrl && pdfCanvasRef.value) {
        loadPdf();
    }
});

onMounted(() => {
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', stopDrag);
    document.addEventListener('mouseleave', stopDrag);
    
    // Wait for canvas to be rendered, then load PDF if URL is available
    setTimeout(() => {
        if (props.pdfBackgroundUrl && pdfCanvasRef.value) {
            loadPdf();
        }
    }, 300);
});

const stopDrag = () => {
    if (isDragging.value || isResizing.value) {
        isDragging.value = false;
        isResizing.value = false;
        resizeHandle.value = null;
    }
};

const updateFieldProperty = (field: Field, property: keyof Field, value: any) => {
    (field as any)[property] = value;
    updateTemplate();
};
</script>

<template>
    <div v-if="!showOnlyCanvas" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- LEFT SIDE: Settings -->
        <div class="lg:col-span-1 space-y-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Fields</CardTitle>
                        <Dialog v-model:open="showFieldDialog">
                            <DialogTrigger as-child>
                                <Button size="sm">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Add Field
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Add Field</DialogTitle>
                                </DialogHeader>
                                <div class="space-y-4 py-4">
                                    <div>
                                        <Label>Label</Label>
                                        <Input v-model="fieldForm.label" placeholder="e.g., Guest Name" />
                                    </div>
                                    <div>
                                        <Label>Column Name</Label>
                                        <Input v-model="fieldForm.column" placeholder="e.g., name" />
                                    </div>
                                    <div>
                                        <Label>Font Size</Label>
                                        <Input v-model.number="fieldForm.fontSize" type="number" />
                                    </div>
                                    <div>
                                        <Label>Text Align</Label>
                                        <select
                                            v-model="fieldForm.textAlign"
                                            class="w-full rounded-md border border-input bg-background px-3 py-2"
                                        >
                                            <option value="left">Left</option>
                                            <option value="center">Center</option>
                                            <option value="right">Right</option>
                                        </select>
                                    </div>
                                    <div>
                                        <Label>Font Family</Label>
                                        <select
                                            v-model="fieldForm.fontFamily"
                                            class="w-full rounded-md border border-input bg-background px-3 py-2"
                                        >
                                            <option value="Arial">Arial</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="Courier New">Courier New</option>
                                            <option value="Helvetica">Helvetica</option>
                                            <option value="Georgia">Georgia</option>
                                            <option value="Verdana">Verdana</option>
                                            <option value="Comic Sans MS">Comic Sans MS</option>
                                        </select>
                                    </div>
                                    <div>
                                        <Label>Font Color</Label>
                                        <Input v-model="fieldForm.fontColor" type="color" class="h-10" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <Label>Font Weight</Label>
                                            <select
                                                v-model="fieldForm.fontWeight"
                                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                                            >
                                                <option value="normal">Normal</option>
                                                <option value="bold">Bold</option>
                                            </select>
                                        </div>
                                        <div>
                                            <Label>Font Style</Label>
                                            <select
                                                v-model="fieldForm.fontStyle"
                                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                                            >
                                                <option value="normal">Normal</option>
                                                <option value="italic">Italic</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <Label>Text Decoration</Label>
                                        <select
                                            v-model="fieldForm.textDecoration"
                                            class="w-full rounded-md border border-input bg-background px-3 py-2"
                                        >
                                            <option value="none">None</option>
                                            <option value="underline">Underline</option>
                                        </select>
                                    </div>
                                    <Button 
                                        @click="addField" 
                                        class="w-full"
                                        :disabled="!canAddField"
                                    >
                                        Add Field
                                    </Button>
                                    <p v-if="!canAddField" class="text-xs text-muted-foreground text-center">
                                        Please fill in Label and Column Name to add field
                                    </p>
                                </div>
                            </DialogContent>
                        </Dialog>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div
                            v-for="field in fields"
                            :key="field.id"
                            :class="[
                                'p-2 border rounded cursor-pointer',
                                selectedField?.id === field.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                            ]"
                            @click="selectField(field)"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">{{ field.label || field.column }}</span>
                                <button
                                    @click.stop="deleteField(field.id)"
                                    class="text-red-500 hover:text-red-700"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <p v-if="fields.length === 0" class="text-sm text-muted-foreground text-center py-4">
                            No fields yet. Click "Add Field" to create one.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="selectedField">
                <CardHeader>
                    <CardTitle>Field Properties</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <Label>Font Size</Label>
                        <Input
                            :value="selectedField.fontSize"
                            type="number"
                            @input="updateFieldProperty(selectedField, 'fontSize', parseFloat(($event.target as HTMLInputElement).value))"
                        />
                    </div>
                    <div>
                        <Label>Text Align</Label>
                        <select
                            :value="selectedField.textAlign"
                            @change="updateFieldProperty(selectedField, 'textAlign', ($event.target as HTMLSelectElement).value)"
                            class="w-full rounded-md border border-input bg-background px-3 py-2"
                        >
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    <div>
                        <Label>Font Family</Label>
                        <select
                            :value="selectedField.fontFamily || 'Arial'"
                            @change="updateFieldProperty(selectedField, 'fontFamily', ($event.target as HTMLSelectElement).value)"
                            class="w-full rounded-md border border-input bg-background px-3 py-2"
                        >
                            <option value="Arial">Arial</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Courier New">Courier New</option>
                            <option value="Helvetica">Helvetica</option>
                            <option value="Georgia">Georgia</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Comic Sans MS">Comic Sans MS</option>
                        </select>
                    </div>
                    <div>
                        <Label>Font Color</Label>
                        <Input
                            :value="selectedField.fontColor || '#000000'"
                            type="color"
                            class="h-10"
                            @input="updateFieldProperty(selectedField, 'fontColor', ($event.target as HTMLInputElement).value)"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <Label>Font Weight</Label>
                            <select
                                :value="selectedField.fontWeight || 'normal'"
                                @change="updateFieldProperty(selectedField, 'fontWeight', ($event.target as HTMLSelectElement).value)"
                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                            >
                                <option value="normal">Normal</option>
                                <option value="bold">Bold</option>
                            </select>
                        </div>
                        <div>
                            <Label>Font Style</Label>
                            <select
                                :value="selectedField.fontStyle || 'normal'"
                                @change="updateFieldProperty(selectedField, 'fontStyle', ($event.target as HTMLSelectElement).value)"
                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                            >
                                <option value="normal">Normal</option>
                                <option value="italic">Italic</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <Label>Text Decoration</Label>
                        <select
                            :value="selectedField.textDecoration || 'none'"
                            @change="updateFieldProperty(selectedField, 'textDecoration', ($event.target as HTMLSelectElement).value)"
                            class="w-full rounded-md border border-input bg-background px-3 py-2"
                        >
                            <option value="none">None</option>
                            <option value="underline">Underline</option>
                        </select>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- RIGHT SIDE: Canvas -->
        <div class="lg:col-span-3 flex flex-col min-h-0">
            <Card class="flex-1 flex flex-col min-h-0">
                <CardContent class="p-0 flex-1 overflow-hidden min-h-0">
                    <div class="flex justify-center items-center h-full overflow-auto p-4 bg-gray-50 rounded-lg">
                        <div class="relative border-2 border-dashed border-gray-400 bg-white shadow-lg" 
                             ref="canvasRef"
                             :style="{ 
                                 width: scaledWidth + 'px', 
                                 height: scaledHeight + 'px',
                                 minWidth: scaledWidth + 'px',
                                 minHeight: scaledHeight + 'px',
                                 aspectRatio: (props.template.width / props.template.height).toString(),
                             }">
                            <!-- PDF Background Canvas -->
                            <canvas
                                v-if="props.pdfBackgroundUrl"
                                ref="pdfCanvasRef"
                                class="absolute inset-0 w-full h-full pointer-events-none"
                                style="z-index: 1; object-fit: contain;"
                            />
                            <div
                                v-if="pdfLoading"
                                class="absolute inset-0 flex items-center justify-center bg-gray-100"
                                style="z-index: 1;"
                            >
                                <span class="text-sm text-gray-500">Loading PDF...</span>
                            </div>
                            <div
                                v-if="pdfError"
                                class="absolute inset-0 flex items-center justify-center bg-red-50"
                                style="z-index: 1;"
                            >
                                <span class="text-sm text-red-500">{{ pdfError }}</span>
                            </div>
                            <div
                                v-for="field in fields"
                                :key="field.id"
                                :class="[
                                    'absolute border-2 cursor-move select-none',
                                    selectedField?.id === field.id 
                                        ? 'border-blue-600 shadow-lg bg-transparent' 
                                        : 'border-blue-400 bg-transparent',
                                ]"
                                :style="{ ...getFieldStyle(field), zIndex: selectedField?.id === field.id ? 30 : 20 }"
                                @mousedown.prevent="startDrag($event, field)"
                                @click.stop="selectField(field)"
                            >
                                <div class="pointer-events-none w-full flex" :style="{
                                    textAlign: field.textAlign || 'left',
                                    justifyContent: field.textAlign === 'center' ? 'center' : field.textAlign === 'right' ? 'flex-end' : 'flex-start',
                                    padding: '3px',
                                    margin: 0,
                                }">
                                    <span :style="{
                                        fontFamily: field.fontFamily || 'Arial',
                                        color: field.fontColor || '#000000',
                                        fontWeight: field.fontWeight || 'normal',
                                        fontStyle: field.fontStyle || 'normal',
                                        textDecoration: field.textDecoration || 'none',
                                        fontSize: `${(field.fontSize || 16) * scale}px`,
                                        display: 'inline-block',
                                        width: '100%',
                                        lineHeight: 1,
                                        padding: 0,
                                        margin: 0,
                                    }">{{ field.label || field.column }}</span>
                                </div>
                                <div
                                    v-if="selectedField?.id === field.id"
                                    class="resize-handle absolute bottom-0 right-0 h-2 w-2 cursor-se-resize bg-blue-500"
                                    @mousedown.stop="startResize($event, 'se')"
                                ></div>
                                <div
                                    v-if="selectedField?.id === field.id"
                                    class="resize-handle absolute top-0 right-0 h-2 w-2 cursor-ne-resize bg-blue-500"
                                    @mousedown.stop="startResize($event, 'ne')"
                                ></div>
                                <div
                                    v-if="selectedField?.id === field.id"
                                    class="resize-handle absolute bottom-0 left-0 h-2 w-2 cursor-sw-resize bg-blue-500"
                                    @mousedown.stop="startResize($event, 'sw')"
                                ></div>
                                <div
                                    v-if="selectedField?.id === field.id"
                                    class="resize-handle absolute top-0 left-0 h-2 w-2 cursor-nw-resize bg-blue-500"
                                    @mousedown.stop="startResize($event, 'nw')"
                                ></div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
    <!-- Canvas Only Mode -->
    <div v-else class="h-full">
        <Card class="h-full flex flex-col">
            <CardContent class="flex-1 overflow-auto p-0">
                <div class="flex justify-center items-center h-full overflow-auto p-4 bg-gray-50 rounded-lg">
                    <div class="relative border-2 border-dashed border-gray-400 bg-white shadow-lg" 
                         ref="canvasRef"
                         :style="{ 
                             width: scaledWidth + 'px', 
                             height: scaledHeight + 'px',
                             minWidth: scaledWidth + 'px',
                             minHeight: scaledHeight + 'px',
                             aspectRatio: (props.template.width / props.template.height).toString(),
                         }">
                        <!-- PDF Background Canvas -->
                        <canvas
                            v-if="props.pdfBackgroundUrl"
                            ref="pdfCanvasRef"
                            class="absolute inset-0 w-full h-full pointer-events-none"
                            style="z-index: 1; object-fit: contain;"
                        />
                        <div
                            v-if="pdfLoading"
                            class="absolute inset-0 flex items-center justify-center bg-gray-100"
                            style="z-index: 1;"
                        >
                            <span class="text-sm text-gray-500">Loading PDF...</span>
                        </div>
                        <div
                            v-if="pdfError"
                            class="absolute inset-0 flex items-center justify-center bg-red-50"
                            style="z-index: 1;"
                        >
                            <span class="text-sm text-red-500">{{ pdfError }}</span>
                        </div>
                        <div
                            v-for="field in fields"
                            :key="field.id"
                            :class="[
                                'absolute border-2 cursor-move select-none',
                                selectedField?.id === field.id 
                                    ? 'border-blue-600 shadow-lg bg-transparent' 
                                    : 'border-blue-400 bg-transparent',
                            ]"
                            :style="{ ...getFieldStyle(field), zIndex: selectedField?.id === field.id ? 30 : 20 }"
                            @mousedown.prevent="startDrag($event, field)"
                            @click.stop="selectField(field)"
                        >
                            <div class="pointer-events-none w-full flex" :style="{
                                textAlign: field.textAlign || 'left',
                                justifyContent: field.textAlign === 'center' ? 'center' : field.textAlign === 'right' ? 'flex-end' : 'flex-start',
                                padding: '3px',
                                margin: 0,
                            }">
                                <span :style="{
                                    fontFamily: field.fontFamily || 'Arial',
                                    color: field.fontColor || '#000000',
                                    fontWeight: field.fontWeight || 'normal',
                                    fontStyle: field.fontStyle || 'normal',
                                    textDecoration: field.textDecoration || 'none',
                                    fontSize: `${(field.fontSize || 16) * scale}px`,
                                    display: 'inline-block',
                                    width: '100%',
                                    lineHeight: 1,
                                    padding: 0,
                                    margin: 0,
                                }">{{ field.label || field.column }}</span>
                            </div>
                            <div
                                v-if="selectedField?.id === field.id"
                                class="resize-handle absolute bottom-0 right-0 h-2 w-2 cursor-se-resize bg-blue-500"
                                @mousedown.stop="startResize($event, 'se')"
                            ></div>
                            <div
                                v-if="selectedField?.id === field.id"
                                class="resize-handle absolute top-0 right-0 h-2 w-2 cursor-ne-resize bg-blue-500"
                                @mousedown.stop="startResize($event, 'ne')"
                            ></div>
                            <div
                                v-if="selectedField?.id === field.id"
                                class="resize-handle absolute bottom-0 left-0 h-2 w-2 cursor-sw-resize bg-blue-500"
                                @mousedown.stop="startResize($event, 'sw')"
                            ></div>
                            <div
                                v-if="selectedField?.id === field.id"
                                class="resize-handle absolute top-0 left-0 h-2 w-2 cursor-nw-resize bg-blue-500"
                                @mousedown.stop="startResize($event, 'nw')"
                            ></div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>


