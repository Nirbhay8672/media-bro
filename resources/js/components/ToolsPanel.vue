<script setup lang="ts">
import { Move, Type, Image, Square, Circle, Triangle, Star } from 'lucide-vue-next';

interface Props {
    selectedTool: string;
}

interface Emits {
    (e: 'update:selectedTool', value: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Tool options
const tools = [
    { id: 'select', name: 'Select', icon: Move },
    { id: 'text', name: 'Text', icon: Type },
    { id: 'image', name: 'Image', icon: Image },
    { id: 'rectangle', name: 'Rectangle', icon: Square },
    { id: 'circle', name: 'Circle', icon: Circle },
    { id: 'triangle', name: 'Triangle', icon: Triangle },
    { id: 'star', name: 'Star', icon: Star },
];

const selectTool = (toolId: string) => {
    emit('update:selectedTool', toolId);
};
</script>

<template>
    <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg shadow-lg border border-gray-200/60 dark:border-gray-700/60">
        <div class="p-2 border-b border-gray-200/60 dark:border-gray-700/60">
            <h2 class="text-xs font-semibold text-gray-900 dark:text-white">Tools</h2>
        </div>
        <div class="p-2">
            <div class="grid grid-cols-4 gap-1">
                <button
                    v-for="tool in tools"
                    :key="tool.id"
                    type="button"
                    @click="selectTool(tool.id)"
                    :class="[
                        'flex items-center justify-center w-7 h-7 rounded-md transition-colors',
                        selectedTool === tool.id
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                    ]"
                    :title="tool.name"
                >
                    <component :is="tool.icon" class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>
