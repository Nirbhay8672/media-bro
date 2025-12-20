<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Upload, FileSpreadsheet, Eye } from 'lucide-vue-next';
import axios from 'axios';

interface Props {
    excelData?: any[];
    noCard?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    uploaded: [data: { data: any[]; columns: string[] }];
    viewFile: [];
    loading: [loading: boolean, message?: string];
    cancelRequest: [];
}>();

const isUploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const uploadedData = ref(props.excelData || []);
let uploadAbortController: AbortController | null = null;

// Expose cancel function for parent component
const cancelUpload = () => {
    if (uploadAbortController) {
        uploadAbortController.abort();
        uploadAbortController = null;
    }
    isUploading.value = false;
    emit('loading', false);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// Expose cancelUpload to parent via defineExpose
defineExpose({
    cancelUpload
});

// Cleanup on unmount
onBeforeUnmount(() => {
    if (uploadAbortController) {
        uploadAbortController.abort();
    }
});

const handleFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (!file) return;

    if (!file.name.match(/\.(xlsx|xls|csv)$/i)) {
        alert('Please upload a valid Excel file (.xlsx, .xls, or .csv)');
        return;
    }

    isUploading.value = true;
    emit('loading', true, 'Uploading Excel file...');
    
    try {
        const formData = new FormData();
        formData.append('excel_file', file);

        emit('loading', true, 'Uploading Excel file...');
        
        // Create abort controller for cancellation
        uploadAbortController = new AbortController();
        
        let uploadComplete = false;
        const response = await axios.post('/pdf-templates/upload-excel', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            signal: uploadAbortController.signal,
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total && !uploadComplete) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    // Only show progress if less than 100% (avoid showing 100% before processing)
                    if (percentCompleted < 100) {
                        emit('loading', true, `Uploading Excel file... ${percentCompleted}%`);
                    } else {
                        // When upload reaches 100%, switch to processing message
                        uploadComplete = true;
                        emit('loading', true, 'Processing Excel file...');
                    }
                }
            },
        });
        
        // After upload completes, show processing message
        emit('loading', true, 'Processing Excel file...');

        uploadedData.value = response.data.data || [];
        emit('uploaded', {
            data: response.data.data || [],
            columns: response.data.columns || [],
        });
    } catch (error: any) {
        // Don't show error if request was cancelled
        if (axios.isCancel(error) || error.name === 'AbortError' || error.code === 'ERR_CANCELED') {
            console.log('Excel upload was cancelled');
            return;
        }
        
        alert('Error uploading file: ' + (error.response?.data?.message || error.message));
    } finally {
        isUploading.value = false;
        emit('loading', false);
        uploadAbortController = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};
</script>

<template>
    <Card v-if="!noCard">
        <CardHeader>
            <CardTitle>Upload Excel File</CardTitle>
            <CardDescription>
                Upload an Excel file (.xlsx, .xls, or .csv) containing your data
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <input
                    ref="fileInput"
                    type="file"
                    accept=".xlsx,.xls,.csv"
                    class="hidden"
                    @change="handleFileSelect"
                />
                <button
                    type="button"
                    @click="triggerFileInput"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium transition-colors cursor-pointer"
                >
                    <FileSpreadsheet class="h-4 w-4" />
                    <span>Upload File</span>
                </button>

                <div v-if="isUploading" class="text-center py-4">
                    <p class="text-sm text-gray-600">Uploading and processing file...</p>
                </div>

                <div v-if="uploadedData.length > 0" class="mt-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <FileSpreadsheet class="h-5 w-5 text-green-500" />
                            <span class="text-sm font-semibold text-gray-700">
                                File uploaded
                            </span>
                        </div>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="emit('viewFile')"
                            class="flex items-center gap-2"
                        >
                            <Eye class="h-4 w-4" />
                            View File
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
    <div v-else class="space-y-4">
        <input
            ref="fileInput"
            type="file"
            accept=".xlsx,.xls,.csv"
            class="hidden"
            @change="handleFileSelect"
        />
        <button
            type="button"
            @click="triggerFileInput"
            class="w-full flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium transition-colors cursor-pointer"
        >
            <FileSpreadsheet class="h-4 w-4" />
            <span>Upload File</span>
        </button>

        <div v-if="isUploading" class="text-center py-4">
            <p class="text-sm text-gray-600">Uploading and processing file...</p>
        </div>

        <div v-if="uploadedData.length > 0" class="mt-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <FileSpreadsheet class="h-5 w-5 text-green-500" />
                    <span class="text-sm font-semibold text-gray-700">
                        File uploaded
                    </span>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    @click="emit('viewFile')"
                    class="flex items-center gap-2"
                >
                    <Eye class="h-4 w-4" />
                    View File
                </Button>
            </div>
        </div>
    </div>
</template>


