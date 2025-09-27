<script setup lang="ts">
// No imports needed for image logo
</script>

<template>
    <div class="flex items-center gap-4">
        <div class="flex aspect-square size-12 items-center justify-center rounded-lg overflow-hidden bg-sidebar-accent/20 p-1">
            <img 
                src="/images/logo.png" 
                alt="Media Bro Logo" 
                class="w-full h-full object-contain"
            />
        </div>
        <div class="grid flex-1 text-left">
            <span class="text-base font-bold truncate leading-tight text-sidebar-foreground"
                >Media Bro</span
            >
            <span class="text-sm truncate text-sidebar-foreground/70 font-medium">Design Studio</span>
        </div>
    </div>
</template>
