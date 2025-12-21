<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch, onBeforeUnmount, nextTick, type ComponentPublicInstance } from 'vue';
import PdfTemplateBuilder from '@/components/PdfTemplate/PdfTemplateBuilder.vue';
import ExcelUploader from '@/components/PdfTemplate/ExcelUploader.vue';
import PdfUploader from '@/components/PdfTemplate/PdfUploader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X, Plus, Pencil, Eye } from 'lucide-vue-next';
import axios from 'axios';

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
    pageNumber?: number; // Page number (1-10)
}

// A4 dimensions: 210mm × 297mm (standard paper size)
const A4_WIDTH = 210; // mm
const A4_HEIGHT = 297; // mm

interface Page {
    id: string;
    fields: Field[];
}

const template = ref<{
    name: string;
    width: number;
    height: number;
    pages: Page[];
}>({
    name: '',
    width: A4_WIDTH, // A4 width in mm (fixed)
    height: A4_HEIGHT, // A4 height in mm (fixed)
    pages: [{ 
        id: '1', 
        fields: [
            {
                id: 'default-1',
                x: 10,
                y: 10,
                width: 100,
                height: 30,
                label: 'Name',
                column: 'name',
                fontSize: 20,
                textAlign: 'center',
                fontFamily: 'Helvetica',
                fontColor: '#ffffff',
                fontWeight: 'bold',
                fontStyle: 'normal',
                textDecoration: 'none',
            }
        ] 
    }], // Start with one page with default field
});

const excelData = ref<any[]>([]);
const excelColumns = ref<string[]>([]);
const columnMapping = ref<Record<string, string>>({});
const generatedPdfs = ref<any[]>([]);
const isGenerating = ref(false);
const showPreviewModal = ref(false);
const pdfPreviewUrl = ref<string | null>(null);
const modalKey = ref(0); // Force Dialog re-render on each generation
const showFieldDialog = ref(false);
const showEditFieldDialog = ref(false);
const showExcelViewModal = ref(false);
const selectedField = ref<Field | null>(null);
const editingField = ref<Field | null>(null);
const uploadedPdfUrl = ref<string>('');
const uploadedPdfPath = ref<string>('');
const pdfPageImageBase64 = ref<string | string[]>([]);
const fieldForm = ref({
    label: '',
    column: '',
    fontSize: 16,
    textAlign: 'left' as 'left' | 'center' | 'right',
    fontFamily: 'Arial',
    fontColor: '#000000',
    fontWeight: 'normal' as 'normal' | 'bold',
    fontStyle: 'normal' as 'normal' | 'italic',
    textDecoration: 'none' as 'none' | 'underline',
});

const currentPageIndex = ref(0);
const currentPage = computed(() => template.value.pages[currentPageIndex.value] || template.value.pages[0]);
const hasTemplate = computed(() => template.value.pages.some(page => page.fields.length > 0));
const hasExcelData = computed(() => excelData.value.length > 0);
const globalLoading = ref(false);
const loadingMessage = ref('Processing...');
const loadingType = ref<'upload' | 'generate' | 'download' | 'pdf-load' | 'default'>('default');
let generateAbortController: AbortController | null = null;
let generateTimeoutId: ReturnType<typeof setTimeout> | null = null;
let progressUpdateInterval: ReturnType<typeof setInterval> | null = null;
const pdfUploaderRef = ref<ComponentPublicInstance | null>(null);
const excelUploaderRef = ref<ComponentPublicInstance | null>(null);

const cancelPdfGeneration = () => {
    if (generateAbortController) {
        generateAbortController.abort();
        generateAbortController = null;
    }
    isGenerating.value = false;
    globalLoading.value = false;
    loadingType.value = 'default';
    loadingMessage.value = 'Processing...';
    
    if (progressUpdateInterval) {
        clearInterval(progressUpdateInterval);
        progressUpdateInterval = null;
    }
    if (generateTimeoutId) {
        clearTimeout(generateTimeoutId);
        generateTimeoutId = null;
    }
};

const cancelUpload = () => {
    // Try to call cancel method on uploader components
    if (pdfUploaderRef.value && typeof (pdfUploaderRef.value as any).cancelUpload === 'function') {
        (pdfUploaderRef.value as any).cancelUpload();
    }
    if (excelUploaderRef.value && typeof (excelUploaderRef.value as any).cancelUpload === 'function') {
        (excelUploaderRef.value as any).cancelUpload();
    }
};

// Ensure loading state is reset on component unmount
onBeforeUnmount(() => {
    if (globalLoading.value) {
        globalLoading.value = false;
    }
    // Clean up any pending requests
    if (generateAbortController) {
        generateAbortController.abort();
    }
    if (generateTimeoutId) {
        clearTimeout(generateTimeoutId);
    }
    if (progressUpdateInterval) {
        clearInterval(progressUpdateInterval);
    }
    // Clean up blob URL
    if (pdfPreviewUrl.value) {
        URL.revokeObjectURL(pdfPreviewUrl.value);
        pdfPreviewUrl.value = null;
    }
});

// Page management functions
const addPage = () => {
    if (template.value.pages.length >= 10) {
        alert('Maximum 10 pages allowed');
        return;
    }
    const newPageId = (template.value.pages.length + 1).toString();
    template.value.pages.push({ id: newPageId, fields: [] });
    currentPageIndex.value = template.value.pages.length - 1;
    handleTemplateUpdate(template.value);
};

const deletePage = (pageIndex: number) => {
    if (template.value.pages.length <= 1) {
        alert('At least one page is required');
        return;
    }
    template.value.pages.splice(pageIndex, 1);
    if (currentPageIndex.value >= template.value.pages.length) {
        currentPageIndex.value = template.value.pages.length - 1;
    }
    handleTemplateUpdate(template.value);
    selectedField.value = null;
};

const switchPage = (pageIndex: number) => {
    currentPageIndex.value = pageIndex;
    selectedField.value = null;
};

const handleTemplateUpdate = (updatedTemplate: any) => {
    template.value = { ...updatedTemplate };
};

const handlePdfUpload = (data: any) => {
    uploadedPdfUrl.value = data.file_url || '';
    uploadedPdfPath.value = data.file_path || '';
    
    // Update template dimensions based on uploaded PDF
    if (data.dimensions) {
        template.value.width = data.dimensions.width || A4_WIDTH;
        template.value.height = data.dimensions.height || A4_HEIGHT;
    }
};

// Expose loading state to child components
const setGlobalLoading = (loading: boolean, message: string = 'Processing...', type: 'upload' | 'generate' | 'download' | 'pdf-load' | 'default' = 'default') => {
    globalLoading.value = loading;
    loadingMessage.value = message;
    loadingType.value = type;
};

const handlePdfImageConverted = (base64: string | string[]) => {
    pdfPageImageBase64.value = base64;
};

const handlePdfPagesDetected = (numPages: number) => {
    // If we have fewer pages in template than PDF pages, add missing pages
    if (numPages > template.value.pages.length) {
        const currentPageCount = template.value.pages.length;
        for (let i = currentPageCount; i < numPages; i++) {
            const newPageId = (i + 1).toString();
            template.value.pages.push({ id: newPageId, fields: [] });
        }
    }
    // If we have more pages in template than PDF pages, remove extra pages (but keep at least 1)
    else if (numPages < template.value.pages.length && numPages > 0) {
        template.value.pages = template.value.pages.slice(0, numPages);
        if (currentPageIndex.value >= template.value.pages.length) {
            currentPageIndex.value = template.value.pages.length - 1;
        }
    }
    
    handleTemplateUpdate(template.value);
};

const handleExcelUpload = (data: any) => {
    excelData.value = data.data || [];
    excelColumns.value = data.columns || [];

    // Improved auto-mapping: try to match field columns to Excel columns
    const autoMapping: Record<string, string> = {};
    
    // Map fields from all pages
    template.value.pages.forEach((page) => {
        page.fields.forEach((field) => {
            if (field.column) {
                // Try exact match first (case-insensitive)
                let matchedColumn = excelColumns.value.find(
                    (col) => col.toLowerCase() === field.column.toLowerCase()
                );
                
                // If no exact match, try matching with label
                if (!matchedColumn) {
                    matchedColumn = excelColumns.value.find(
                        (col) => col.toLowerCase() === field.label?.toLowerCase()
                    );
                }
                
                // If still no match, try partial match
                if (!matchedColumn) {
                    matchedColumn = excelColumns.value.find(
                        (col) => col.toLowerCase().includes(field.column.toLowerCase()) ||
                                 field.column.toLowerCase().includes(col.toLowerCase())
                    );
                }
                
                if (matchedColumn) {
                    autoMapping[field.column] = matchedColumn;
                }
            }
        });
    });
    
    columnMapping.value = autoMapping;
};


const generatePdfs = async () => {
    if (!hasTemplate.value) {
        alert('Please create a template first');
        return;
    }
    if (!hasExcelData.value) {
        alert('Please upload an Excel file first');
        return;
    }

    // Ensure all fields have column mappings
    const missingMappings: string[] = [];
    template.value.pages.forEach((page) => {
        page.fields.forEach((field) => {
            if (field.column && !columnMapping.value[field.column]) {
                // Try to auto-map if not already mapped
                const matchingColumn = excelColumns.value.find(
                    (col) => col.toLowerCase() === field.column.toLowerCase() ||
                             col.toLowerCase() === field.label.toLowerCase()
                );
                if (matchingColumn) {
                    columnMapping.value[field.column] = matchingColumn;
                } else {
                    missingMappings.push(field.label || field.column);
                }
            }
        });
    });

    // Check if there are still unmapped fields
    if (missingMappings.length > 0) {
        alert(`Please map the following fields to Excel columns:\n${missingMappings.join(', ')}\n\nYou can map them by matching field column names with Excel column names.`);
        return;
    }

    // Ensure column_mapping is not empty
    if (Object.keys(columnMapping.value).length === 0) {
        alert('No column mappings found. Please ensure your field column names match Excel column names.');
        return;
    }

    // Cancel any previous request
    if (generateAbortController) {
        generateAbortController.abort();
    }
    if (generateTimeoutId) {
        clearTimeout(generateTimeoutId);
        generateTimeoutId = null;
    }
    if (progressUpdateInterval) {
        clearInterval(progressUpdateInterval);
        progressUpdateInterval = null;
    }
    
    // Create new abort controller
    generateAbortController = new AbortController();
    
    // Set generating state (only to disable button, no popup)
    isGenerating.value = true;
    
    try {
        const requestStartTime = Date.now();
        
        // Add timeout warning (increased to 30 seconds)
        const timeoutWarning = setTimeout(() => {
            // Timeout warning (silent)
        }, 30000);
        
        let response;
        try {
            response = await axios.post('/pdf-templates/generate', {
                template: template.value,
                excel_data: excelData.value,
                column_mapping: columnMapping.value,
                pdf_file_path: uploadedPdfPath.value || null,
                pdf_page_image: pdfPageImageBase64.value || null,
            }, {
                timeout: 1800000, // 30 minutes timeout (increased from 10 minutes)
                signal: generateAbortController.signal,
                maxContentLength: Infinity,
                maxBodyLength: Infinity,
                onUploadProgress: (progressEvent) => {
                    // Upload progress tracking (silent)
                },
            });
            
            clearTimeout(timeoutWarning);
        } catch (requestError) {
            clearTimeout(timeoutWarning);
            throw requestError;
        }

        const requestDuration = Date.now() - requestStartTime;

        // Process successful response
        if (!response || !response.data) {
            throw new Error('No data received from server');
        }

        // Check for success flag from backend (if present)
        if (response.data.success === false) {
            throw new Error(response.data.message || 'PDF generation failed');
        }

        // Get PDFs from response
        const pdfsData = response.data.pdfs || [];
        
        if (!Array.isArray(pdfsData)) {
            throw new Error('Invalid response format from server: pdfs is not an array');
        }
        
        // Validate PDFs were generated
        if (pdfsData.length === 0) {
            throw new Error('No PDFs were generated. Please check your template and data.');
        }

        // Set generated PDFs
        generatedPdfs.value = pdfsData;
        
        // Create blob URL for preview
        if (generatedPdfs.value.length > 0 && generatedPdfs.value[0]?.base64) {
            try {
                if (pdfPreviewUrl.value) {
                    URL.revokeObjectURL(pdfPreviewUrl.value);
                }
                const base64String = generatedPdfs.value[0].base64.trim();
                if (base64String && base64String.length > 0) {
                    const byteCharacters = atob(base64String);
                    const byteNumbers = new Array(byteCharacters.length);
                    for (let i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    const byteArray = new Uint8Array(byteNumbers);
                    const blob = new Blob([byteArray], { type: 'application/pdf' });
                    pdfPreviewUrl.value = URL.createObjectURL(blob);
                }
            } catch (error) {
                pdfPreviewUrl.value = null;
            }
        }
        
        // Reset generating state
        isGenerating.value = false;
        globalLoading.value = false;
        loadingType.value = 'default';
        
        // FORCE OPEN MODAL - Multiple aggressive attempts
        modalKey.value++;
        showPreviewModal.value = true;
        
        // Force with nextTick
        await nextTick();
        showPreviewModal.value = true;
        
        // Force with requestAnimationFrame
        requestAnimationFrame(() => {
            showPreviewModal.value = true;
        });
        
        // Force with multiple timeouts
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 0);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 50);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 100);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 200);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 500);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 1000);
        
    } catch (error: any) {
        // Don't show error if request was cancelled
        if (axios.isCancel(error) || error.name === 'AbortError' || error.code === 'ERR_CANCELED') {
            isGenerating.value = false;
            return;
        }
        
        let errorMessage = 'Error generating PDFs. ';
        
        if (error.code === 'ECONNABORTED' || error.message?.includes('timeout')) {
            errorMessage += 'The request timed out. Please try again with fewer rows or check server logs.';
        } else if (error.response) {
            const responseData = error.response.data;
            if (responseData?.errors) {
                const validationErrors = Object.values(responseData.errors).flat();
                errorMessage += validationErrors.join(', ') || 'Validation error occurred.';
            } else {
                errorMessage += responseData?.message || `Server error (${error.response.status}). Check server logs.`;
            }
        } else if (error.request) {
            errorMessage += 'No response from server. Please check your connection and server logs.';
        } else {
            errorMessage += error.message || 'Unknown error occurred.';
        }
        
        alert(errorMessage);
    } finally {
        // Always reset generating state
        isGenerating.value = false;
        
        // Cleanup
        if (progressUpdateInterval) {
            clearInterval(progressUpdateInterval);
            progressUpdateInterval = null;
        }
        if (generateTimeoutId) {
            clearTimeout(generateTimeoutId);
            generateTimeoutId = null;
        }
        generateAbortController = null;
    }
};


const downloadPdfFromBase64 = (pdf: { filename: string; base64: string }) => {
    try {
        const byteCharacters = atob(pdf.base64);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'application/pdf' });
        
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = pdf.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        setTimeout(() => URL.revokeObjectURL(url), 100);
    } catch (error) {
        const link = document.createElement('a');
        link.href = `data:application/pdf;base64,${pdf.base64}`;
        link.download = pdf.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

const downloadAllPdfs = async () => {
    if (generatedPdfs.value.length === 0) return;
    
    for (let index = 0; index < generatedPdfs.value.length; index++) {
        downloadPdfFromBase64(generatedPdfs.value[index]);
        if (index < generatedPdfs.value.length - 1) {
            await new Promise(resolve => setTimeout(resolve, 200));
        }
    }
};

const canAddField = computed(() => {
    return fieldForm.value.label?.trim() && fieldForm.value.column?.trim();
});

const addField = () => {
    if (!canAddField.value) return;
    
    const newField = {
        id: Date.now().toString(),
        x: 10,
        y: 10,
        width: 100,
        height: 0, // 0 means auto height, will be set when user resizes
        column: fieldForm.value.column.trim(),
        label: fieldForm.value.label.trim(),
        fontSize: fieldForm.value.fontSize || 16,
        textAlign: fieldForm.value.textAlign || 'left',
        fontFamily: fieldForm.value.fontFamily || 'Arial',
        fontColor: fieldForm.value.fontColor || '#000000',
        fontWeight: fieldForm.value.fontWeight || 'normal',
        fontStyle: fieldForm.value.fontStyle || 'normal',
        textDecoration: fieldForm.value.textDecoration || 'none',
    };
    
    currentPage.value.fields.push(newField);
    handleTemplateUpdate(template.value);
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
    const page = template.value.pages.find(p => p.fields.some(f => f.id === fieldId));
    if (page) {
        const index = page.fields.findIndex((f) => f.id === fieldId);
        if (index > -1) {
            page.fields.splice(index, 1);
            handleTemplateUpdate(template.value);
            if (selectedField.value?.id === fieldId) {
                selectedField.value = null;
            }
        }
    }
};

const selectField = (field: Field | null) => {
    selectedField.value = field;
};

const editField = (field: Field) => {
    editingField.value = field;
    fieldForm.value = {
        label: field.label,
        column: field.column,
        fontSize: field.fontSize,
        textAlign: field.textAlign,
        fontFamily: field.fontFamily || 'Arial',
        fontColor: field.fontColor || '#000000',
        fontWeight: field.fontWeight || 'normal',
        fontStyle: field.fontStyle || 'normal',
        textDecoration: field.textDecoration || 'none',
    };
    showEditFieldDialog.value = true;
};

const updateField = () => {
    if (!editingField.value) return;
    
    const page = template.value.pages.find(p => p.fields.some(f => f.id === editingField.value!.id));
    if (page) {
        const field = page.fields.find(f => f.id === editingField.value!.id);
        if (field) {
            field.label = fieldForm.value.label.trim();
            field.column = fieldForm.value.column.trim();
            field.fontSize = fieldForm.value.fontSize || 16;
            field.textAlign = fieldForm.value.textAlign || 'left';
            field.fontFamily = fieldForm.value.fontFamily || 'Arial';
            field.fontColor = fieldForm.value.fontColor || '#000000';
            field.fontWeight = fieldForm.value.fontWeight || 'normal';
            field.fontStyle = fieldForm.value.fontStyle || 'normal';
            field.textDecoration = fieldForm.value.textDecoration || 'none';
            handleTemplateUpdate(template.value);
            showEditFieldDialog.value = false;
            editingField.value = null;
        }
    }
};

// Reset form when Add Field dialog opens
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

// Reset form when Edit Field dialog closes
watch(showEditFieldDialog, (isOpen) => {
    if (!isOpen && editingField.value === null) {
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

// Watch for generated PDFs and open modal automatically
// FORCE OPEN MODAL when PDFs are generated - Aggressive
watch(generatedPdfs, (newPdfs, oldPdfs) => {
    if (newPdfs && newPdfs.length > 0 && (!oldPdfs || oldPdfs.length === 0)) {
        // Immediate
        showPreviewModal.value = true;
        
        // With nextTick
        nextTick().then(() => {
            showPreviewModal.value = true;
        });
        
        // With requestAnimationFrame
        requestAnimationFrame(() => {
            showPreviewModal.value = true;
        });
        
        // Multiple timeouts
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 0);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 50);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 100);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 200);
        
        setTimeout(() => {
            showPreviewModal.value = true;
        }, 500);
    }
}, { deep: true, immediate: false });


</script>

<template>
    <Head title="PDF Template" />

    <AppLayout>
        <!-- Global Loading Overlay - Bootstrap Modal Style -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="globalLoading"
                class="fixed inset-0 z-[9999] flex items-center justify-center"
                style="pointer-events: all;"
                @click.stop
                @mousedown.stop
                @touchstart.stop
            >
                <!-- Backdrop with fade effect -->
                <div 
                    class="absolute inset-0 bg-black transition-opacity duration-300"
                    :class="globalLoading ? 'opacity-50' : 'opacity-0'"
                    style="backdrop-filter: blur(2px);"
                ></div>
                
                <!-- Modal Content -->
                <div class="relative z-10">
                    <div class="bg-white rounded-lg p-8 shadow-2xl max-w-md w-full mx-4 border border-gray-200">
                        <div class="flex flex-col items-center space-y-6">
                    <!-- Upload Animation -->
                    <div v-if="loadingType === 'upload'" class="w-24 h-24">
                        <svg viewBox="0 0 100 100" class="w-full h-full">
                            <!-- Cloud with gradient fill -->
                            <defs>
                                <linearGradient id="cloudGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#60a5fa;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <path
                                d="M 30 50 Q 20 50 20 60 Q 20 70 30 70 L 70 70 Q 80 70 80 60 Q 80 50 70 50"
                                fill="url(#cloudGradient)"
                                stroke="#2563eb"
                                stroke-width="2"
                                class="animate-pulse"
                            />
                            <!-- Arrow going up with vibrant colors -->
                            <line
                                x1="50"
                                y1="40"
                                x2="50"
                                y2="25"
                                stroke="#10b981"
                                stroke-width="5"
                                stroke-linecap="round"
                                class="animate-bounce"
                            />
                            <polyline
                                points="45,30 50,25 55,30"
                                fill="#10b981"
                                stroke="#10b981"
                                stroke-width="5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="animate-bounce"
                            />
                            <!-- Decorative dots -->
                            <circle cx="30" cy="60" r="2" fill="#fbbf24" class="animate-pulse" style="animation-delay: 0.2s;" />
                            <circle cx="70" cy="60" r="2" fill="#f59e0b" class="animate-pulse" style="animation-delay: 0.4s;" />
                        </svg>
                    </div>
                    
                    <!-- PDF Generation Animation -->
                    <div v-else-if="loadingType === 'generate'" class="w-24 h-24 relative">
                        <svg viewBox="0 0 100 100" class="w-full h-full">
                            <defs>
                                <linearGradient id="pdfGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#60a5fa;stop-opacity:0.2" />
                                    <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:0.3" />
                                </linearGradient>
                                <linearGradient id="textGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#34d399;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#10b981;stop-opacity:1" />
                                </linearGradient>
                                <linearGradient id="imageGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#fbbf24;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#f59e0b;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <!-- Document/PDF icon in center with gradient -->
                            <rect
                                x="25"
                                y="15"
                                width="50"
                                height="70"
                                rx="2"
                                fill="url(#pdfGradient)"
                                stroke="#3b82f6"
                                stroke-width="3"
                                class="animate-pulse"
                            />
                            <!-- Text lines in PDF with colors -->
                            <line
                                x1="35"
                                y1="30"
                                x2="65"
                                y2="30"
                                stroke="#6366f1"
                                stroke-width="2.5"
                                class="animate-pulse"
                            />
                            <line
                                x1="35"
                                y1="45"
                                x2="60"
                                y2="45"
                                stroke="#8b5cf6"
                                stroke-width="2.5"
                                class="animate-pulse"
                                style="animation-delay: 0.2s;"
                            />
                            <line
                                x1="35"
                                y1="60"
                                x2="55"
                                y2="60"
                                stroke="#a855f7"
                                stroke-width="2.5"
                                class="animate-pulse"
                                style="animation-delay: 0.4s;"
                            />
                            <!-- Converting elements (text icon on left) with gradient -->
                            <g class="animate-bounce" style="animation-duration: 1.5s;">
                                <rect x="8" y="32" width="10" height="10" rx="1.5" fill="url(#textGradient)" stroke="#059669" stroke-width="1" />
                                <line x1="10" y1="36" x2="16" y2="36" stroke="white" stroke-width="1.5" />
                                <line x1="10" y1="39" x2="16" y2="39" stroke="white" stroke-width="1.5" />
                            </g>
                            <!-- Converting elements (image icon on left) with gradient -->
                            <g class="animate-bounce" style="animation-duration: 1.5s; animation-delay: 0.3s;">
                                <rect x="8" y="48" width="10" height="10" rx="1.5" fill="url(#imageGradient)" stroke="#d97706" stroke-width="1" />
                                <circle cx="13" cy="53" r="2.5" fill="white" />
                                <circle cx="11" cy="51" r="1" fill="#fef3c7" />
                            </g>
                            <!-- Arrows pointing to PDF (left side) with vibrant colors -->
                            <g class="animate-pulse" style="animation-duration: 1.5s;">
                                <line x1="20" y1="37" x2="25" y2="37" stroke="#10b981" stroke-width="3" stroke-linecap="round" />
                                <polyline points="23,35 25,37 23,39" fill="#10b981" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                <line x1="20" y1="53" x2="25" y2="53" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" />
                                <polyline points="23,51 25,53 23,55" fill="#f59e0b" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                            </g>
                            <!-- Converting elements (text icon on right) with gradient -->
                            <g class="animate-bounce" style="animation-duration: 1.5s; animation-delay: 0.5s;">
                                <rect x="82" y="32" width="10" height="10" rx="1.5" fill="url(#textGradient)" stroke="#059669" stroke-width="1" />
                                <line x1="84" y1="36" x2="90" y2="36" stroke="white" stroke-width="1.5" />
                                <line x1="84" y1="39" x2="90" y2="39" stroke="white" stroke-width="1.5" />
                            </g>
                            <!-- Converting elements (image icon on right) with gradient -->
                            <g class="animate-bounce" style="animation-duration: 1.5s; animation-delay: 0.8s;">
                                <rect x="82" y="48" width="10" height="10" rx="1.5" fill="url(#imageGradient)" stroke="#d97706" stroke-width="1" />
                                <circle cx="87" cy="53" r="2.5" fill="white" />
                                <circle cx="85" cy="51" r="1" fill="#fef3c7" />
                            </g>
                            <!-- Arrows pointing to PDF (right side) with vibrant colors -->
                            <g class="animate-pulse" style="animation-duration: 1.5s; animation-delay: 0.5s;">
                                <line x1="75" y1="37" x2="70" y2="37" stroke="#10b981" stroke-width="3" stroke-linecap="round" />
                                <polyline points="72,35 70,37 72,39" fill="#10b981" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                <line x1="75" y1="53" x2="70" y2="53" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" />
                                <polyline points="72,51 70,53 72,55" fill="#f59e0b" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                            </g>
                        </svg>
                    </div>
                    
                    <!-- Download Animation -->
                    <div v-else-if="loadingType === 'download'" class="w-24 h-24">
                        <svg viewBox="0 0 100 100" class="w-full h-full">
                            <defs>
                                <linearGradient id="downloadGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                                </linearGradient>
                                <linearGradient id="docGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.4" />
                                    <stop offset="100%" style="stop-color:#2563eb;stop-opacity:0.6" />
                                </linearGradient>
                            </defs>
                            <!-- Arrow going down with gradient -->
                            <line
                                x1="50"
                                y1="25"
                                x2="50"
                                y2="60"
                                stroke="url(#downloadGradient)"
                                stroke-width="5"
                                stroke-linecap="round"
                                class="animate-bounce"
                            />
                            <polyline
                                points="45,55 50,60 55,55"
                                fill="url(#downloadGradient)"
                                stroke="url(#downloadGradient)"
                                stroke-width="5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="animate-bounce"
                            />
                            <!-- Document at bottom with gradient -->
                            <rect
                                x="35"
                                y="65"
                                width="30"
                                height="20"
                                rx="2"
                                fill="url(#docGradient)"
                                stroke="#3b82f6"
                                stroke-width="2"
                                class="animate-pulse"
                            />
                            <!-- Decorative lines in document -->
                            <line x1="40" y1="72" x2="60" y2="72" stroke="#60a5fa" stroke-width="1.5" class="animate-pulse" />
                            <line x1="40" y1="78" x2="55" y2="78" stroke="#60a5fa" stroke-width="1.5" class="animate-pulse" style="animation-delay: 0.2s;" />
                        </svg>
                    </div>
                    
                    <!-- PDF Loading Animation -->
                    <div v-else-if="loadingType === 'pdf-load'" class="w-24 h-24">
                        <svg viewBox="0 0 100 100" class="w-full h-full">
                            <defs>
                                <linearGradient id="page1Gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#c084fc;stop-opacity:0.3" />
                                    <stop offset="100%" style="stop-color:#a855f7;stop-opacity:0.4" />
                                </linearGradient>
                                <linearGradient id="page2Gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#818cf8;stop-opacity:0.4" />
                                    <stop offset="100%" style="stop-color:#6366f1;stop-opacity:0.5" />
                                </linearGradient>
                                <linearGradient id="page3Gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#60a5fa;stop-opacity:0.5" />
                                    <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:0.6" />
                                </linearGradient>
                            </defs>
                            <!-- PDF pages stacking with gradients -->
                            <rect
                                x="25"
                                y="20"
                                width="50"
                                height="60"
                                rx="2"
                                fill="url(#page1Gradient)"
                                stroke="#a855f7"
                                stroke-width="2"
                                class="animate-pulse"
                                style="animation-delay: 0.4s;"
                            />
                            <rect
                                x="27"
                                y="22"
                                width="50"
                                height="60"
                                rx="2"
                                fill="url(#page2Gradient)"
                                stroke="#6366f1"
                                stroke-width="2"
                                class="animate-pulse"
                                style="animation-delay: 0.2s;"
                            />
                            <rect
                                x="29"
                                y="24"
                                width="50"
                                height="60"
                                rx="2"
                                fill="url(#page3Gradient)"
                                stroke="#3b82f6"
                                stroke-width="3"
                                class="animate-pulse"
                            />
                            <!-- Text lines on top page -->
                            <line x1="35" y1="35" x2="65" y2="35" stroke="#60a5fa" stroke-width="2" class="animate-pulse" />
                            <line x1="35" y1="45" x2="60" y2="45" stroke="#818cf8" stroke-width="2" class="animate-pulse" style="animation-delay: 0.2s;" />
                            <line x1="35" y1="55" x2="55" y2="55" stroke="#a855f7" stroke-width="2" class="animate-pulse" style="animation-delay: 0.4s;" />
                        </svg>
                    </div>
                    
                    <!-- Default Spinner -->
                    <div v-else class="relative w-12 h-12">
                        <svg class="animate-spin w-12 h-12" viewBox="0 0 24 24">
                            <defs>
                                <linearGradient id="spinnerGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                                    <stop offset="25%" style="stop-color:#8b5cf6;stop-opacity:1" />
                                    <stop offset="50%" style="stop-color:#ec4899;stop-opacity:1" />
                                    <stop offset="75%" style="stop-color:#f59e0b;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#10b981;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <circle
                                cx="12"
                                cy="12"
                                r="10"
                                fill="none"
                                stroke="url(#spinnerGradient)"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-dasharray="60"
                                stroke-dashoffset="15"
                            />
                        </svg>
                    </div>
                    
                    <p class="text-gray-700 font-medium text-center text-lg">{{ loadingMessage }}</p>
                    <!-- Progress indicator and cancel buttons -->
                    <div v-if="loadingType === 'generate' || loadingType === 'upload'" class="w-full max-w-xs mt-4 space-y-3">
                        <div v-if="loadingType === 'generate'" class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-full animate-pulse" style="width: 100%; background-size: 200% 100%; background-image: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%); animation: shimmer 2s ease-in-out infinite;"></div>
                        </div>
                        <!-- Cancel button -->
                        <Button
                            @click="loadingType === 'generate' ? cancelPdfGeneration() : cancelUpload()"
                            variant="outline"
                            size="sm"
                            class="w-full"
                        >
                            {{ loadingType === 'generate' ? 'Cancel Generation' : 'Cancel Upload' }}
                        </Button>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
        
        <div 
            class="h-[calc(100vh-4rem)] flex flex-col p-4 overflow-hidden" 
            :class="{ 'pointer-events-none': globalLoading }"
            :style="globalLoading ? { 'user-select': 'none' } : {}"
        >
            <!-- Header -->
            <div class="flex items-center justify-between mb-4 flex-shrink-0">
                <div>
                    <h1 class="text-2xl font-semibold">PDF Template</h1>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Page Selector -->
                    <div class="flex items-center gap-2">
                        <Label class="text-sm whitespace-nowrap">Page:</Label>
                        <div class="flex items-center gap-1">
                            <Button
                                v-for="(page, index) in template.pages"
                                :key="page.id"
                                size="sm"
                                variant="outline"
                                :class="currentPageIndex === index ? 'bg-blue-500 text-white border-blue-500' : ''"
                                @click="switchPage(index)"
                            >
                                {{ index + 1 }}
                            </Button>
                        </div>
                    </div>
                    <Button
                        @click="generatePdfs"
                        :disabled="!hasTemplate || !hasExcelData || isGenerating"
                    >
                        {{ isGenerating ? 'Generating…' : 'Generate & Preview' }}
                    </Button>
                </div>
            </div>

            <!-- Main Content: Left side with Fields and Upload Excel, Right side with Canvas -->
            <div class="flex-1 flex gap-4 overflow-hidden min-h-0">
                <!-- Left Side: Single card with Fields and Upload Excel -->
                <Card class="w-80 flex-shrink-0 flex flex-col overflow-hidden">
                    <CardContent class="flex-1 overflow-auto min-h-0 space-y-6">
                        <!-- Fields Section -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <Label class="text-base font-semibold">Fields</Label>
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
                                            <DialogDescription>Add a new field to the template</DialogDescription>
                                        </DialogHeader>
                                        <div class="space-y-4 py-4">
                                            <div>
                                                <Label class="mb-2 block text-sm font-medium">Label</Label>
                                                <Input v-model="fieldForm.label" placeholder="e.g., Guest Name" />
                                            </div>
                                            <div>
                                                <Label class="mb-2 block text-sm font-medium">Column Name</Label>
                                                <Input v-model="fieldForm.column" placeholder="e.g., name" />
                                            </div>
                                            <div>
                                                <Label class="mb-2 block text-sm font-medium">Font Size</Label>
                                                <Input v-model.number="fieldForm.fontSize" type="number" min="8" max="72" />
                                            </div>
                                            <div>
                                                <Label class="mb-2 block text-sm font-medium">Text Align</Label>
                                                <select
                                                    v-model="fieldForm.textAlign"
                                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    <option value="left">Left</option>
                                                    <option value="center">Center</option>
                                                    <option value="right">Right</option>
                                                </select>
                                            </div>
                                            <div>
                                                <Label class="mb-2 block text-sm font-medium">Font Family</Label>
                                                <select
                                                    v-model="fieldForm.fontFamily"
                                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
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
                                                <Label class="mb-2 block text-sm font-medium">Font Color</Label>
                                                <div class="flex items-center gap-2">
                                                    <Input v-model="fieldForm.fontColor" type="color" class="h-9 w-20 cursor-pointer" />
                                                    <Input v-model="fieldForm.fontColor" type="text" placeholder="#000000" class="flex-1" />
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <Label class="mb-2 block text-sm font-medium">Font Weight</Label>
                                                    <select
                                                        v-model="fieldForm.fontWeight"
                                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        <option value="normal">Normal</option>
                                                        <option value="bold">Bold</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <Label class="mb-2 block text-sm font-medium">Font Style</Label>
                                                    <select
                                                        v-model="fieldForm.fontStyle"
                                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        <option value="normal">Normal</option>
                                                        <option value="italic">Italic</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div>
                                                <Label class="mb-2 block text-sm font-medium">Text Decoration</Label>
                                                <select
                                                    v-model="fieldForm.textDecoration"
                                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    <option value="none">None</option>
                                                    <option value="underline">Underline</option>
                                                </select>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button 
                                                @click="addField" 
                                                class="w-full sm:w-auto"
                                                :disabled="!canAddField"
                                            >
                                                Add Field
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="field in currentPage.fields"
                                    :key="field.id"
                                    :class="[
                                        'group p-2 border rounded cursor-pointer transition-colors',
                                        selectedField?.id === field.id ? 'border-blue-500 bg-blue-100' : 'border-gray-200 hover:bg-gray-50'
                                    ]"
                                    @click="selectField(field)"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span :class="[
                                                'text-sm font-semibold transition-colors',
                                                selectedField?.id === field.id 
                                                    ? 'text-blue-700' 
                                                    : 'text-gray-900 group-hover:text-blue-600'
                                            ]">
                                                {{ field.label || field.column }}
                                            </span>
                                            <span v-if="field.label && field.column" :class="[
                                                'text-xs mt-0.5 transition-colors',
                                                selectedField?.id === field.id 
                                                    ? 'text-gray-600' 
                                                    : 'text-gray-600 group-hover:text-gray-800'
                                            ]">
                                                Column: {{ field.column }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1 ml-2">
                                            <button
                                                @click.stop="editField(field)"
                                                class="text-blue-500 hover:text-blue-700 p-1"
                                                title="Edit field"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click.stop="deleteField(field.id)"
                                                class="text-red-500 hover:text-red-700 p-1"
                                                title="Delete field"
                                            >
                                                <X class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="currentPage.fields.length === 0" class="text-sm text-muted-foreground text-center py-4">
                                    No fields yet. Click "Add Field" to create one.
                                </p>
                            </div>
                        </div>

                        <!-- Upload PDF Section -->
                        <div class="border-t pt-4">
                            <Label class="text-base font-semibold mb-3 block">Upload PDF Template</Label>
                            <PdfUploader
                                ref="pdfUploaderRef"
                                @uploaded="handlePdfUpload"
                                :pdf-url="uploadedPdfUrl"
                                :no-card="true"
                                @loading="(loading, message) => { globalLoading = loading; loadingMessage = message || 'Processing...'; loadingType = loading ? 'upload' : 'default'; }"
                            />
                        </div>

                        <!-- Upload Excel Section -->
                        <div class="border-t pt-4">
                            <Label class="text-base font-semibold mb-3 block">Upload Excel</Label>
                            <ExcelUploader
                                ref="excelUploaderRef"
                                @uploaded="handleExcelUpload"
                                :excel-data="excelData"
                                :no-card="true"
                                @view-file="showExcelViewModal = true"
                                @loading="(loading, message) => { globalLoading = loading; loadingMessage = message || 'Processing...'; loadingType = loading ? 'upload' : 'default'; }"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Edit Field Dialog -->
                <Dialog v-model:open="showEditFieldDialog">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit Field</DialogTitle>
                            <DialogDescription>Edit field properties</DialogDescription>
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
                            <div class="flex gap-2">
                                <Button 
                                    @click="updateField" 
                                    class="flex-1"
                                    :disabled="!fieldForm.label?.trim() || !fieldForm.column?.trim()"
                                >
                                    Update Field
                                </Button>
                                <Button 
                                    variant="outline"
                                    @click="() => { showEditFieldDialog = false; editingField = null; }" 
                                    class="flex-1"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                <!-- Right Side: Canvas Only -->
                <div class="flex-1 min-w-0 overflow-hidden">
                    <PdfTemplateBuilder
                        :template="{
                            name: template.name,
                            width: template.width,
                            height: template.height,
                            fields: currentPage.fields
                        }"
                        :show-only-canvas="true"
                        :selected-field-id="selectedField?.id || null"
                        :pdf-background-url="uploadedPdfUrl"
                        :current-page-number="currentPageIndex + 1"
                        @update="(updatedTemplate) => {
                            currentPage.fields = updatedTemplate.fields;
                            handleTemplateUpdate(template);
                        }"
                        @field-selected="selectField"
                        @pdf-image-converted="handlePdfImageConverted"
                        @pdf-pages-detected="handlePdfPagesDetected"
                        @loading="(loading, message) => { globalLoading = loading; loadingMessage = message || 'Processing...'; loadingType = loading ? 'pdf-load' : 'default'; }"
                    />
                </div>
            </div>
        </div>

        <!-- PDF Preview Modal -->
        <Dialog :open="showPreviewModal" @update:open="showPreviewModal = $event" :key="`preview-modal-${modalKey}`">
            <DialogContent class="!w-[80vw] !max-w-[80vw] max-h-[90vh] overflow-hidden flex flex-col mx-auto">
                <DialogHeader>
                    <DialogTitle>PDF Preview</DialogTitle>
                    <DialogDescription>Preview and download generated PDFs</DialogDescription>
                </DialogHeader>
                <div class="flex-1 overflow-auto">
                    <div v-if="generatedPdfs.length === 0" class="text-center py-8 text-gray-500">
                        No PDFs generated yet.
                    </div>
                    <div v-else class="space-y-4">
                        <!-- PDF Preview -->
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-white">
                                <div class="aspect-[1/1.414] w-full" style="min-height: 600px;">
                                    <!-- Try blob URL first -->
                                    <iframe
                                        v-if="pdfPreviewUrl"
                                        :src="pdfPreviewUrl"
                                        class="w-full h-full border-0"
                                    ></iframe>
                                    <!-- Fallback to data URI -->
                                    <iframe
                                        v-else-if="generatedPdfs[0]?.base64"
                                        :src="`data:application/pdf;base64,${generatedPdfs[0].base64}`"
                                        class="w-full h-full border-0"
                                    ></iframe>
                                    <!-- Fallback to object tag -->
                                    <object
                                        v-else-if="generatedPdfs[0]?.base64"
                                        :data="`data:application/pdf;base64,${generatedPdfs[0].base64}`"
                                        type="application/pdf"
                                        class="w-full h-full border-0"
                                    >
                                        <p class="flex items-center justify-center h-full text-gray-500">
                                            PDF cannot be displayed. <a :href="`data:application/pdf;base64,${generatedPdfs[0].base64}`" download="preview.pdf" class="text-blue-500 underline ml-2">Download instead</a>
                                        </p>
                                    </object>
                                    <div v-else class="flex items-center justify-center h-full text-red-500">
                                        <p>PDF data is missing</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <Button
                        variant="outline"
                        @click="showPreviewModal = false"
                    >
                        Close
                    </Button>
                    <Button
                        v-if="generatedPdfs.length > 0"
                        @click="downloadAllPdfs"
                    >
                        Download All PDFs ({{ generatedPdfs.length }})
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Excel View Modal -->
        <Dialog v-model:open="showExcelViewModal">
            <DialogContent class="!w-[80vw] !max-w-[80vw] max-h-[90vh] overflow-hidden flex flex-col mx-auto">
                <DialogHeader>
                    <DialogTitle>Excel File Data</DialogTitle>
                    <DialogDescription>View uploaded Excel file data</DialogDescription>
                </DialogHeader>
                <div class="flex-1 overflow-auto">
                    <div v-if="excelData.length === 0" class="text-center py-8 text-gray-500">
                        No Excel data available.
                    </div>
                    <div v-else class="border rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-700 sticky top-0 z-10 shadow-md">
                                    <tr>
                                        <th
                                            v-for="(column, index) in excelColumns"
                                            :key="index"
                                            class="px-4 py-3 text-left font-bold border-b border-slate-600 text-white uppercase tracking-wide"
                                        >
                                            {{ column }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <tr
                                        v-for="(row, rowIndex) in excelData"
                                        :key="rowIndex"
                                        class="border-b border-gray-200 hover:bg-blue-50 transition-colors"
                                    >
                                        <td
                                            v-for="(column, colIndex) in excelColumns"
                                            :key="colIndex"
                                            class="px-4 py-2 text-gray-800 font-medium"
                                        >
                                            {{ row[column] }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 bg-gray-50 border-t text-sm text-gray-600 text-center">
                            Total rows: {{ excelData.length }}
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}
</style>


