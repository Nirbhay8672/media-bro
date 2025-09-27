<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import templates from '@/routes/templates';
import { type BreadcrumbItem } from '@/types';
import { Plus, Edit, Trash2, Eye, Share2, Copy } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Templates',
        href: templates.index().url,
    },
];

interface Template {
    id: number;
    name: string;
    description?: string;
    width: number;
    height: number;
    background_image?: string;
    share_token: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    templates: Template[];
}>();


const deleteTemplate = (id: number) => {
    if (confirm('Are you sure you want to delete this template?')) {
        router.delete(templates.destroy(id));
    }
};

// Helper function to generate template URLs safely
const getTemplateShowUrl = (templateId: number) => {
    try {
        return templates.show(templateId);
    } catch (error) {
        console.error('Error generating show URL:', error);
        return '#';
    }
};

const getTemplateEditUrl = (templateId: number) => {
    try {
        return templates.edit(templateId);
    } catch (error) {
        console.error('Error generating edit URL:', error);
        return '#';
    }
};

const copyShareLink = (shareToken: string) => {
    const shareUrl = `${window.location.origin}/template/${shareToken}`;
    navigator.clipboard.writeText(shareUrl).then(() => {
        // You could add a toast notification here
        alert('Share link copied to clipboard!');
    });
};
</script>

<template>
    <Head title="Templates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Templates</h1>
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
                                :src="`/storage/${template.background_image}`"
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

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <Link
                                v-if="template.id"
                                :href="getTemplateShowUrl(template.id)"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                <Eye class="h-4 w-4" />
                                View
                            </Link>
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
