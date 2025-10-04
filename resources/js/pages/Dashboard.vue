<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import templates from '@/routes/templates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Image, Eye, Share2, Users, FileText, Loader2 } from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
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

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
}

defineProps<{
    templates?: Template[];
    template_count?: number;
    user_count?: number;
    total_template_count?: number;
    auth: {
        user: User;
    };
}>();

// Get loading state from Inertia
const page = usePage();
const isLoading = computed(() => page.props.processing as boolean);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Welcome Section -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome to Media Bro</h1>
                        <p class="text-gray-600 dark:text-gray-400">Create and share image templates with ease</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- User's Template Count -->
                <Link
                    :href="templates.index()"
                    class="group rounded-xl border border-gray-200 bg-white p-6 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
                >
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <FileText v-if="!isLoading" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                            <Loader2 v-else class="h-8 w-8 animate-spin text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">My Templates</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ template_count || 0 }}</p>
                        </div>
                    </div>
                </Link>

                <!-- Total Users Card (Super Admin Only) -->
                <Link
                    v-if="auth.user.role === 'super_admin' && user_count !== undefined"
                    href="/users"
                    class="group rounded-xl border border-gray-200 bg-white p-6 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
                >
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <Users v-if="!isLoading" class="h-8 w-8 text-green-600 dark:text-green-400" />
                            <Loader2 v-else class="h-8 w-8 animate-spin text-green-600 dark:text-green-400" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user_count || 0 }}</p>
                        </div>
                    </div>
                </Link>

            </div>

            <!-- Recent Templates -->
            <div v-if="templates && templates.length > 0" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Templates</h2>
                    <Link
                        :href="templates.index()"
                        class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        <span>View all</span>
                        <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
                    </Link>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="template in templates.slice(0, 6)"
                        :key="template.id"
                        class="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700"
                    >
                        <!-- Template Preview -->
                        <div class="aspect-video bg-gray-100 dark:bg-gray-600">
                            <div
                                v-if="template.background_image"
                                class="h-full w-full bg-cover bg-center"
                                :style="{ backgroundImage: `url(/storage/${template.background_image})` }"
                            ></div>
                            <div v-else class="flex h-full w-full items-center justify-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ template.width }}x{{ template.height }}</span>
                            </div>
                        </div>

                        <!-- Template Info -->
                        <div class="p-3">
                            <h3 class="font-medium text-gray-900 dark:text-white">{{ template.name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ template.width }}x{{ template.height }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 transition-all group-hover:bg-opacity-20">
                            <div class="absolute bottom-2 right-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                <Link
                                    :href="templates.show(template.id)"
                                    class="rounded bg-white p-1.5 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                                    title="View"
                                >
                                    <Eye v-if="!isLoading" class="h-3 w-3 text-gray-600 dark:text-gray-400" />
                                    <Loader2 v-else class="h-3 w-3 animate-spin text-gray-600 dark:text-gray-400" />
                                </Link>
                                <button
                                    @click="navigator.clipboard.writeText(`${window.location.origin}/template/${template.share_token}`)"
                                    class="rounded bg-white p-1.5 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                                    title="Copy Share Link"
                                >
                                    <Share2 class="h-3 w-3 text-gray-600 dark:text-gray-400" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
