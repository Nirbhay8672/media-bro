<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
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

    isGenerating.value = true;
    try {
        const response = await axios.post('/pdf-templates/generate', {
            template: template.value,
            excel_data: excelData.value,
            column_mapping: columnMapping.value,
            pdf_file_path: uploadedPdfPath.value || null,
            pdf_page_image: pdfPageImageBase64.value || null,
        });

        generatedPdfs.value = response.data.pdfs || [];
        // Open preview modal after successful generation
        if (generatedPdfs.value.length > 0) {
            showPreviewModal.value = true;
        }
    } catch (error: any) {
        alert('Error generating PDFs: ' + (error.response?.data?.message || error.message));
    } finally {
        isGenerating.value = false;
    }
};

const downloadPdfFromBase64 = (pdf: { filename: string; base64: string }) => {
    try {
        // Convert base64 to blob for faster download
        const byteCharacters = atob(pdf.base64);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'application/pdf' });
        
        // Use blob URL for faster download
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = pdf.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Clean up blob URL after a short delay
        setTimeout(() => URL.revokeObjectURL(url), 100);
    } catch (error) {
        // Fallback to data URL if blob fails
        const link = document.createElement('a');
        link.href = `data:application/pdf;base64,${pdf.base64}`;
        link.download = pdf.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

const downloadAllPdfs = () => {
    // Download all PDFs with minimal delay for faster batch downloads
    generatedPdfs.value.forEach((pdf: any, index: number) => {
        setTimeout(() => {
            downloadPdfFromBase64(pdf);
        }, index * 50); // Reduced delay from 100ms to 50ms
    });
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
</script>

<template>
    <Head title="PDF Template" />

    <AppLayout>
        <div class="h-[calc(100vh-4rem)] flex flex-col p-4 overflow-hidden">
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
                            <Button
                                v-if="template.pages.length < 10"
                                size="sm"
                                variant="outline"
                                @click="addPage"
                                title="Add Page"
                            >
                                <Plus class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="template.pages.length > 1"
                                size="sm"
                                variant="outline"
                                @click="deletePage(currentPageIndex)"
                                title="Delete Current Page"
                            >
                                <X class="h-4 w-4" />
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
                                @uploaded="handlePdfUpload"
                                :pdf-url="uploadedPdfUrl"
                                :no-card="true"
                            />
                        </div>

                        <!-- Upload Excel Section -->
                        <div class="border-t pt-4">
                            <Label class="text-base font-semibold mb-3 block">Upload Excel</Label>
                            <ExcelUploader
                                @uploaded="handleExcelUpload"
                                :excel-data="excelData"
                                :no-card="true"
                                @view-file="showExcelViewModal = true"
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
                    />
                </div>
            </div>
        </div>

        <!-- PDF Preview Modal -->
        <Dialog v-model:open="showPreviewModal">
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
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-white">
                                <div class="aspect-[1/1.414] w-full" style="min-height: 600px;">
                                    <iframe
                                        :src="`data:application/pdf;base64,${generatedPdfs[0].base64}`"
                                        class="w-full h-full border-0"
                                    ></iframe>
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


