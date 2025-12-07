<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import templates from '@/routes/templates/index';
import { type BreadcrumbItem } from '@/types';
import { Plus, Edit, Trash2, Share2, Copy } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Image Template',
        href: templates.index().url,
    },
];

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

interface Template {
    id: number;
    name: string;
    description?: string;
    width: number;
    height: number;
    background_image?: string;
    background_image_url?: string;
    canvas_data: CanvasElement[] | string;
    share_token: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    visits_count: number;
    downloads_count: number;
}

const props = defineProps<{
    templates: Template[];
}>();



const deleteTemplate = (id: number) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(templates.destroy(id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Template has been deleted successfully.',
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        toast: true
                    });
                },
                onError: () => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to delete template. Please try again.',
                        icon: 'error',
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                        toast: true
                    });
                }
            });
        }
    });
};

const getTemplateShowUrl = (templateId: number) => {
    try {
        return templates.show(templateId);
    } catch (error) {
        return '#';
    }
};

const getTemplateEditUrl = (templateId: number) => {
    return `/templates/${templateId}/edit`;
};

const copyShareLink = (shareToken: string) => {
    const shareUrl = `${window.location.origin}/template/${shareToken}`;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(shareUrl).then(() => {
            showCopySuccess();
        }).catch(() => {
            fallbackCopyText(shareUrl);
        });
    } else {
        fallbackCopyText(shareUrl);
    }
};

const fallbackCopyText = (text: string) => {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess();
        } else {
            showCopyError();
        }
    } catch (err) {
        showCopyError();
    } finally {
        document.body.removeChild(textArea);
    }
};

const showCopySuccess = () => {
    Swal.fire({
        title: 'Copied!',
        text: 'Share link copied to clipboard!',
        icon: 'success',
        confirmButtonText: 'OK',
        timer: 2000,
        timerProgressBar: true
    });
};

const showCopyError = () => {
    Swal.fire({
        title: 'Error!',
        text: 'Failed to copy link. Please try again.',
        icon: 'error',
        confirmButtonText: 'OK'
    });
};

</script>

<template>
    <Head title="Image Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Image Template</h1>
                    <p class="text-gray-600 dark:text-gray-400">Manage your image templates</p>
                </div>
                <Link
                    :href="templates.create()"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    Create Template
                </Link>
            </div>

            <div v-if="!props.templates || props.templates.length === 0" class="flex flex-1 items-center justify-center">
                <div class="text-center">
                    <div class="mx-auto h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                        <Plus class="h-6 w-6 text-gray-400" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">No templates yet</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Get started by creating your first template.</p>
                    <Link
                        :href="templates.create()"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        <Plus class="h-4 w-4" />
                        Create Template
                    </Link>
                </div>
            </div>

            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="template in props.templates"
                    :key="template.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow"
                >
                    <!-- Template Image -->
                    <div class="relative">
                        <div class="h-48 w-full bg-gray-100 dark:bg-gray-700 rounded-t-lg overflow-hidden">
                            <img
                                v-if="template.background_image"
                                :src="template.background_image_url || `/storage/${template.background_image}`"
                                :alt="template.name"
                                class="w-full h-full object-cover"
                            />
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100"
                            >
                                <div class="text-center">
                                    <Image class="w-12 h-12 mx-auto mb-2 text-gray-400" />
                                    <p class="text-sm text-gray-600">{{ template.width }}x{{ template.height }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3">
                            <span
                                :class="template.is_active ? 'bg-green-500' : 'bg-gray-400'"
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-white"
                            >
                                {{ template.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    <!-- Template Content -->
                    <div class="p-4">
                        <div class="mb-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                {{ template.name }}
                            </h3>
                            <p v-if="template.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ template.description }}
                            </p>
                        </div>

                        <!-- Template Details -->
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                <span>{{ template.width }}×{{ template.height }}</span>
                            </div>
                            <span>{{ new Date(template.created_at).toLocaleDateString() }}</span>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <span>{{ template.visits_count }} views</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>{{ template.downloads_count }} downloads</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <Link
                                v-if="template.id"
                                :href="getTemplateEditUrl(template.id)"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors"
                            >
                                <Edit class="h-4 w-4" />
                                Edit
                            </Link>
                        </div>

                        <!-- Secondary Actions -->
                        <div class="flex gap-2 mt-2">
                            <button
                                @click="copyShareLink(template.share_token)"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900 rounded-lg hover:bg-green-200 dark:hover:bg-green-800 transition-colors"
                            >
                                <Share2 class="h-4 w-4" />
                                Share
                            </button>
                            <button
                                @click="deleteTemplate(template.id)"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition-colors"
                            >
                                <Trash2 class="h-4 w-4" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
