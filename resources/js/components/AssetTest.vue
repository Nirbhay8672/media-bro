<template>
    <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Asset Test Results</h3>
        
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <span class="font-medium">Logo URL:</span>
                <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded text-sm">
                    {{ getLogoUrl() }}
                </code>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="font-medium">Favicon URL:</span>
                <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded text-sm">
                    {{ getFaviconUrl() }}
                </code>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="font-medium">App URL:</span>
                <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded text-sm">
                    {{ getAppUrl() }}
                </code>
            </div>
            
            <div class="mt-4">
                <span class="font-medium">Logo Preview:</span>
                <div class="mt-2">
                    <img 
                        :src="getLogoUrl()" 
                        alt="Logo Test" 
                        class="w-16 h-16 object-contain border border-gray-300 dark:border-gray-600 rounded"
                        @error="logoError = true"
                        @load="logoError = false"
                    />
                    <div v-if="logoError" class="text-red-500 text-sm mt-1">
                        ❌ Logo failed to load
                    </div>
                    <div v-else class="text-green-500 text-sm mt-1">
                        ✅ Logo loaded successfully
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAssets } from '@/composables/useAssets';

const { getLogoUrl, getFaviconUrl, getAppUrl } = useAssets();
const logoError = ref(false);
</script>
