<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Upload, FileText, File } from 'lucide-vue-next';
import axios from 'axios';

interface Props {
    pdfUrl?: string;
    noCard?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    uploaded: [data: { file_path: string; file_url: string; dimensions: { width: number; height: number } }];
    loading: [loading: boolean, message?: string];
    cancelRequest: [];
}>();

const isUploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const uploadedPdfUrl = ref(props.pdfUrl || '');
let uploadAbortController: AbortController | null = null;

// Expose cancel function
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

    if (!file.name.match(/\.(pdf)$/i)) {
        alert('Please upload a valid PDF file (.pdf)');
        return;
    }

    isUploading.value = true;
    emit('loading', true, 'Uploading PDF...');
    
    try {
        // Check file size (50MB = 50 * 1024 * 1024 bytes)
        const maxSize = 50 * 1024 * 1024;
        if (file.size > maxSize) {
            alert(`File size (${(file.size / 1024 / 1024).toFixed(2)}MB) exceeds the maximum allowed size of 50MB.`);
            isUploading.value = false;
            emit('loading', false);
            return;
        }

        emit('loading', true, 'Uploading PDF...');
        const formData = new FormData();
        formData.append('pdf_file', file);

        // Create abort controller for cancellation
        uploadAbortController = new AbortController();

        let uploadComplete = false;
        const response = await axios.post('/pdf-templates/upload-pdf', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            timeout: 120000, // 2 minutes timeout for large files
            signal: uploadAbortController.signal,
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total && !uploadComplete) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    // Only show progress if less than 100% (avoid showing 100% before processing)
                    if (percentCompleted < 100) {
                        emit('loading', true, `Uploading PDF... ${percentCompleted}%`);
                    } else {
                        // When upload reaches 100%, switch to processing message
                        uploadComplete = true;
                        emit('loading', true, 'Processing PDF...');
                    }
                }
            },
        });

        // After upload completes, show processing message
        emit('loading', true, 'Processing PDF...');
        
        uploadedPdfUrl.value = response.data.file_url || '';
        emit('uploaded', {
            file_path: response.data.file_path,
            file_url: response.data.file_url,
            dimensions: response.data.dimensions,
        });
    } catch (error: any) {
        // Don't show error if request was cancelled
        if (axios.isCancel(error) || error.name === 'AbortError' || error.code === 'ERR_CANCELED') {
            console.log('PDF upload was cancelled');
            return;
        }
        
        console.error('PDF Upload Error:', error);
        
        let errorMessage = 'Error uploading file. ';
        
        if (error.response) {
            // Server responded with error
            const errors = error.response.data?.errors;
            if (errors && errors.pdf_file) {
                errorMessage += errors.pdf_file[0] || error.response.data?.message || 'Unknown error';
            } else {
                errorMessage += error.response.data?.message || `Server error (${error.response.status})`;
            }
        } else if (error.request) {
            // Request was made but no response received
            errorMessage += 'No response from server. Please check your connection.';
        } else {
            // Error in setting up the request
            errorMessage += error.message || 'Unknown error occurred';
        }
        
        alert(errorMessage);
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
            <CardTitle>Upload PDF Template</CardTitle>
            <CardDescription>
                Upload a PDF file (.pdf) to use as template background
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <input
                    ref="fileInput"
                    type="file"
                    accept=".pdf"
                    class="hidden"
                    @change="handleFileSelect"
                />
                <button
                    type="button"
                    @click="triggerFileInput"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium transition-colors cursor-pointer"
                >
                    <File class="h-4 w-4" />
                    <span>Upload File</span>
                </button>

                <div v-if="isUploading" class="text-center py-4">
                    <p class="text-sm text-gray-600">Uploading and processing file...</p>
                </div>

                <div v-if="uploadedPdfUrl" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                    <div class="flex items-center gap-2 text-sm text-green-700">
                        <FileText class="h-4 w-4" />
                        <span>PDF uploaded successfully</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
    <div v-else class="space-y-4">
        <input
            ref="fileInput"
            type="file"
            accept=".pdf"
            class="hidden"
            @change="handleFileSelect"
        />
        <button
            type="button"
            @click="triggerFileInput"
            class="w-full flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium transition-colors cursor-pointer"
        >
            <File class="h-4 w-4" />
            <span>Upload File</span>
        </button>

        <div v-if="isUploading" class="text-center py-2">
            <p class="text-sm text-gray-600">Uploading...</p>
        </div>

        <div v-if="uploadedPdfUrl" class="p-2 bg-green-50 border border-green-200 rounded-md">
            <div class="flex items-center gap-2 text-xs text-green-700">
                <FileText class="h-3 w-3" />
                <span>PDF uploaded</span>
            </div>
        </div>
    </div>
</template>

