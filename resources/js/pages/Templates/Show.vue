<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import templates from '@/routes/templates';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Edit, Share2, Copy, Trash2 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Templates',
        href: templates.index().url,
    },
    {
        title: 'Template Details',
        href: '#',
    },
];

interface Template {
    id: number;
    name: string;
    description?: string;
    width: number;
    height: number;
    background_image?: string;
    canvas_data: any[];
    share_token: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

defineProps<{
    template: Template;
}>();

const copyShareLink = (shareToken: string) => {
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const shareUrl = `${origin}/template/${shareToken}`;
    navigator.clipboard.writeText(shareUrl).then(() => {
        alert('Share link copied to clipboard!');
    });
};

const deleteTemplate = (id: number) => {
    if (confirm('Are you sure you want to delete this template?')) {
        router.delete(templates.destroy(id));
    }
};
</script>

<template>
    <Head title="Template Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="templates.index()"
                        class="rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <ArrowLeft class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ template.name }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">Template details and preview</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Link
                        :href="templates.edit(template.id)"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        <Edit class="h-4 w-4" />
                        Edit
                    </Link>
                    <button
                        @click="copyShareLink(template.share_token)"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <Share2 class="h-4 w-4" />
                        Copy Share Link
                    </button>
                    <button
                        @click="deleteTemplate(template.id)"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-600 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-900"
                    >
                        <Trash2 class="h-4 w-4" />
                        Delete
                    </button>
                </div>
            </div>

            <div class="grid flex-1 gap-6 lg:grid-cols-2">
                <!-- Template Preview -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Template Preview</h2>

                    <div class="flex items-center justify-center">
                        <div
                            class="relative border border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700"
                            :style="{
                                width: Math.min(template.width, 500) + 'px',
                                height: Math.min(template.height, 400) + 'px',
                                backgroundImage: template.background_image ? `url(/storage/${template.background_image})` : 'none',
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                            }"
                        >
                            <div v-if="!template.background_image" class="flex h-full w-full items-center justify-center">
                                <div class="text-center">
                                    <div class="mx-auto h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center mb-2">
                                        <span class="text-lg text-gray-500 dark:text-gray-400">📄</span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No background image</p>
                                </div>
                            </div>
                            <div class="absolute bottom-2 right-2 rounded bg-black bg-opacity-50 px-2 py-1 text-xs text-white">
                                {{ template.width }}x{{ template.height }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Information -->
                <div class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Template Information</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Name
                                </label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ template.name }}</p>
                            </div>

                            <div v-if="template.description">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Description
                                </label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ template.description }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Width
                                    </label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ template.width }}px</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Height
                                    </label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ template.height }}px</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Status
                                </label>
                                <span
                                    class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="template.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'"
                                >
                                    {{ template.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Created
                                </label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ new Date(template.created_at).toLocaleDateString() }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Last Updated
                                </label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ new Date(template.updated_at).toLocaleDateString() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Sharing</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Share Link
                                </label>
                                <div class="mt-1 flex gap-2">
                                    <input
                                        :value="`${typeof window !== 'undefined' ? window.location.origin : ''}/template/${template.share_token}`"
                                        readonly
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <button
                                        @click="copyShareLink(template.share_token)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        <Copy class="h-4 w-4" />
                                        Copy
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                                <p class="text-sm text-blue-800 dark:text-blue-300">
                                    <strong>Note:</strong> Anyone with this link can access and edit your template.
                                    Share it carefully and consider making it inactive if you no longer want it to be accessible.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
