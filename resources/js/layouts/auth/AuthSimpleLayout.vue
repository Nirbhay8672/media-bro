<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 p-6 md:p-10"
    >
        <div class="w-full max-w-md">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-20 w-20 items-center justify-center rounded-xl bg-white shadow-xl p-3 border border-gray-100"
                        >
                            <img
                                src="/images/logo.png"
                                alt="Media Bro Logo"
                                class="h-14 w-14 object-contain"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h1>
                        <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8 border border-gray-100 dark:border-gray-700">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
