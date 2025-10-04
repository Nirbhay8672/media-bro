<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div class="flex items-center gap-4">
        <div class="flex aspect-square size-12 items-center justify-center overflow-hidden bg-white rounded-lg p-1">
            <img 
                :src="page.props.logo_url || '/images/logo.png'" 
                alt="Media Bro Logo" 
                class="w-full h-full object-contain"
            />
        </div>
        <div class="grid flex-1 text-left">
            <span class="text-base font-bold truncate leading-tight text-white"
                >Media Bro</span
            >
            <span class="text-sm truncate text-blue-100 font-medium">Design Studio</span>
        </div>
    </div>
</template>
