<script setup lang="ts">
import { Move, Type, Image, Square, Circle, Triangle, Star, Heart } from 'lucide-vue-next';

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
    { id: 'heart', name: 'Heart', icon: Heart },
];

const selectTool = (toolId: string) => {
    emit('update:selectedTool', toolId);
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Tools</h2>
        </div>
        <div class="p-3">
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="tool in tools"
                    :key="tool.id"
                    type="button"
                    @click="selectTool(tool.id)"
                    :class="[
                        'flex items-center justify-center w-8 h-8 rounded-lg transition-colors',
                        selectedTool === tool.id
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                    ]"
                    :title="tool.name"
                >
                    <component :is="tool.icon" class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
