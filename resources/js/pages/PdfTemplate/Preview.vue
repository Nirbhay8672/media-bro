<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Download, ArrowLeft } from 'lucide-vue-next';

interface Pdf {
    filename: string;
    base64: string;
    index: number;
}

interface Props {
    pdfs: Pdf[];
    timestamp?: number;
}

const props = defineProps<Props>();

// Load PDFs from localStorage (stored by Index page)
const loadedPdfs = ref<Pdf[]>([]);

// Try to load from localStorage first
const loadPdfsFromStorage = () => {
    try {
        const storageKey = sessionStorage.getItem('current_pdfs_key');
        if (storageKey) {
            const storedData = localStorage.getItem(storageKey);
            if (storedData) {
                const data = JSON.parse(storedData);
                if (data.pdfs && Array.isArray(data.pdfs)) {
                    loadedPdfs.value = data.pdfs;
                    console.log('PDFs loaded from localStorage:', loadedPdfs.value.length);
                    // Clean up after loading
                    localStorage.removeItem(storageKey);
                    sessionStorage.removeItem('current_pdfs_key');
                    return;
                }
            }
        }
    } catch (error) {
        console.error('Error loading PDFs from localStorage:', error);
    }
    
    // Fallback: use props if available
    if (props.pdfs && props.pdfs.length > 0) {
        loadedPdfs.value = props.pdfs;
        console.log('PDFs loaded from props:', loadedPdfs.value.length);
    }
};

// Load PDFs on mount
loadPdfsFromStorage();

const pdfs = computed(() => loadedPdfs.value.length > 0 ? loadedPdfs.value : (props.pdfs || []));

console.log('Preview page loaded:', {
    pdfs_from_props: props.pdfs?.length || 0,
    pdfs_loaded: pdfs.value.length,
});

const currentPdfIndex = ref(0);
const currentPdf = computed(() => pdfs.value[currentPdfIndex.value] || pdfs.value[0] || null);
const pdfPreviewUrl = ref<string | null>(null);

// Create blob URL for current PDF preview
if (currentPdf.value?.base64) {
    try {
        const base64String = currentPdf.value.base64.trim();
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
        console.error('Error creating blob URL:', error);
    }
}

// Update preview URL when PDF changes
const updatePreviewUrl = (pdf: Pdf) => {
    if (pdfPreviewUrl.value) {
        URL.revokeObjectURL(pdfPreviewUrl.value);
        pdfPreviewUrl.value = null;
    }
    
    if (pdf?.base64) {
        try {
            const base64String = pdf.base64.trim();
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
            console.error('Error creating blob URL:', error);
        }
    }
};

const selectPdf = (index: number) => {
    currentPdfIndex.value = index;
    updatePreviewUrl(pdfs.value[index]);
};

const downloadPdf = (pdf: Pdf) => {
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
        console.error('Error downloading PDF:', error);
        // Fallback to data URL
        const link = document.createElement('a');
        link.href = `data:application/pdf;base64,${pdf.base64}`;
        link.download = pdf.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

const downloadAllPdfs = async () => {
    for (let index = 0; index < pdfs.value.length; index++) {
        downloadPdf(pdfs.value[index]);
        // Small delay to prevent browser from blocking multiple downloads
        if (index < pdfs.value.length - 1) {
            await new Promise(resolve => setTimeout(resolve, 200));
        }
    }
};

const goBack = () => {
    router.visit('/pdf-templates');
};
</script>

<template>
    <Head title="PDF Preview" />

    <AppLayout>
        <div class="h-[calc(100vh-4rem)] flex flex-col p-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <Button
                        variant="outline"
                        @click="goBack"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold">PDF Preview</h1>
                        <p class="text-sm text-gray-500">Preview and download generated PDFs</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        @click="downloadPdf(currentPdf)"
                        :disabled="!currentPdf"
                    >
                        <Download class="mr-2 h-4 w-4" />
                        Download Current PDF
                    </Button>
                    <Button
                        @click="downloadAllPdfs"
                        :disabled="pdfs.length === 0"
                    >
                        <Download class="mr-2 h-4 w-4" />
                        Download All PDFs ({{ pdfs.length }})
                    </Button>
                </div>
            </div>

            <!-- Main Content -->
            <div v-if="!pdfs || pdfs.length === 0" class="flex-1 flex items-center justify-center">
                <Card class="max-w-md">
                    <CardHeader>
                        <CardTitle>No PDFs Found</CardTitle>
                        <CardDescription>No PDFs were found. Please generate PDFs first.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button @click="goBack" class="w-full">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Go Back to PDF Templates
                        </Button>
                    </CardContent>
                </Card>
            </div>
            <div v-else class="flex-1 flex gap-4 overflow-hidden min-h-0">
                <!-- Left Sidebar: PDF List -->
                <Card class="w-64 flex-shrink-0 flex flex-col overflow-hidden">
                    <CardHeader>
                        <CardTitle>Generated PDFs</CardTitle>
                        <CardDescription>{{ pdfs.length }} PDF{{ pdfs.length !== 1 ? 's' : '' }} generated</CardDescription>
                    </CardHeader>
                    <CardContent class="flex-1 overflow-auto min-h-0">
                        <div v-if="!pdfs || pdfs.length === 0" class="text-center py-8 text-gray-500">
                            <p>No PDFs available</p>
                            <Button
                                variant="outline"
                                @click="goBack"
                                class="mt-4"
                            >
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Go Back
                            </Button>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="(pdf, index) in pdfs"
                                :key="index"
                                :class="[
                                    'p-3 border rounded cursor-pointer transition-colors',
                                    currentPdfIndex === index 
                                        ? 'border-blue-500 bg-blue-50' 
                                        : 'border-gray-200 hover:bg-gray-50'
                                ]"
                                @click="selectPdf(index)"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium truncate">
                                            {{ pdf.filename }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            PDF #{{ pdf.index }}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click.stop="downloadPdf(pdf)"
                                        class="ml-2"
                                    >
                                        <Download class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Right Side: PDF Preview -->
                <div class="flex-1 min-w-0 overflow-hidden">
                    <Card class="h-full flex flex-col overflow-hidden">
                        <CardHeader>
                            <CardTitle>
                                {{ currentPdf?.filename || 'No PDF Selected' }}
                            </CardTitle>
                            <CardDescription v-if="currentPdf">
                                PDF {{ currentPdfIndex + 1 }} of {{ pdfs.length }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="flex-1 overflow-auto min-h-0">
                            <div v-if="!currentPdf" class="flex items-center justify-center h-full text-gray-500">
                                <p>No PDF selected</p>
                            </div>
                            <div v-else class="border rounded-lg overflow-hidden bg-white">
                                <div class="aspect-[1/1.414] w-full" style="min-height: 600px;">
                                    <!-- Try blob URL first -->
                                    <iframe
                                        v-if="pdfPreviewUrl"
                                        :src="pdfPreviewUrl"
                                        class="w-full h-full border-0"
                                        @error="(e) => { console.error('PDF iframe error:', e); }"
                                    ></iframe>
                                    <!-- Fallback to data URI -->
                                    <iframe
                                        v-else-if="currentPdf.base64"
                                        :src="`data:application/pdf;base64,${currentPdf.base64}`"
                                        class="w-full h-full border-0"
                                        @error="(e) => { console.error('PDF iframe error:', e); }"
                                    ></iframe>
                                    <!-- Fallback to object tag -->
                                    <object
                                        v-else-if="currentPdf.base64"
                                        :data="`data:application/pdf;base64,${currentPdf.base64}`"
                                        type="application/pdf"
                                        class="w-full h-full border-0"
                                    >
                                        <div class="flex items-center justify-center h-full text-gray-500">
                                            <p>
                                                PDF cannot be displayed. 
                                                <a 
                                                    :href="`data:application/pdf;base64,${currentPdf.base64}`" 
                                                    :download="currentPdf.filename" 
                                                    class="text-blue-500 underline ml-2"
                                                >
                                                    Download instead
                                                </a>
                                            </p>
                                        </div>
                                    </object>
                                    <div v-else class="flex items-center justify-center h-full text-red-500">
                                        <p>PDF data is missing</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

