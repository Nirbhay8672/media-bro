<script setup lang="ts">
import { ref, computed } from 'vue';
import { ChevronDown, Loader2, Upload } from 'lucide-vue-next';

interface Props {
    form: {
        name: string;
        width: number;
        height: number;
        background_image: File | null;
    };
    backgroundImagePreview: string | null;
    selectedQuickTemplate: { name: string; width: number; height: number } | null;
    isQuickTemplateDropdownOpen: boolean;
    isEditMode?: boolean;
    isSubmitting?: boolean;
}

interface Emits {
    (e: 'update:form', value: any): void;
    (e: 'update:backgroundImagePreview', value: string | null): void;
    (e: 'update:selectedQuickTemplate', value: { name: string; width: number; height: number } | null): void;
    (e: 'update:isQuickTemplateDropdownOpen', value: boolean): void;
    (e: 'backgroundImageChange', file: File): void;
    (e: 'applyQuickTemplate', template: { name: string; width: number; height: number }): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Removed search functionality for simplified dropdown

// Quick templates - simplified to 4 essential options
const quickTemplates = [
    { name: 'Instagram Post', width: 1080, height: 1080 },
    { name: 'Instagram Story', width: 1080, height: 1920 },
    { name: 'Facebook Post', width: 1200, height: 630 },
    { name: 'Custom Size', width: 800, height: 600 }
];

// No need for filtering or grouping with only 4 options

const updateForm = (field: string, value: any) => {
    // Validate width and height
    if (field === 'width' || field === 'height') {
        const numValue = parseInt(value) || 100;
        // Ensure minimum size of 100px and maximum of 4000px
        const clampedValue = Math.max(100, Math.min(4000, numValue));
        emit('update:form', { ...props.form, [field]: clampedValue });
    } else {
        emit('update:form', { ...props.form, [field]: value });
    }
};

const handleBackgroundImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            emit('update:backgroundImagePreview', e.target?.result as string);
        };
        reader.readAsDataURL(file);
        emit('backgroundImageChange', file);
    }
};

const applyQuickTemplate = (template: { name: string; width: number; height: number }) => {
    emit('applyQuickTemplate', template);
};

const toggleQuickTemplateDropdown = () => {
    emit('update:isQuickTemplateDropdownOpen', !props.isQuickTemplateDropdownOpen);
};

const closeDropdownOnOutsideClick = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.quick-template-dropdown')) {
        emit('update:isQuickTemplateDropdownOpen', false);
    }
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        emit('update:isQuickTemplateDropdownOpen', false);
    }
};
</script>

<template>
    <div class="w-full">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Template Settings</h2>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Template Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Template Name *
                    </label>
                    <input
                        :value="form.name"
                        @input="updateForm('name', ($event.target as HTMLInputElement).value)"
                        type="text"
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors text-sm"
                        placeholder="Enter template name"
                    />
                </div>

                <!-- Quick Templates -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Quick Templates
                    </label>
                    <div class="relative quick-template-dropdown">
                        <button
                            type="button"
                            @click="toggleQuickTemplateDropdown"
                            class="w-full flex items-center justify-between px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <span class="flex items-center">
                                {{ selectedQuickTemplate ? selectedQuickTemplate.name : 'Select a template...' }}
                                <span v-if="selectedQuickTemplate" class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                    ({{ selectedQuickTemplate.width }} × {{ selectedQuickTemplate.height }}px)
                                </span>
                            </span>
                            <ChevronDown class="h-4 w-4 text-gray-400" />
                        </button>
                        <div
                            v-if="isQuickTemplateDropdownOpen"
                            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg"
                        >
                            <button
                                v-for="template in quickTemplates"
                                :key="template.name"
                                type="button"
                                @click="applyQuickTemplate(template)"
                                class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 text-sm first:rounded-t-lg last:rounded-b-lg"
                            >
                                <div class="font-medium">{{ template.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ template.width }} × {{ template.height }}px
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Canvas Size -->
                <div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <input
                                :value="form.width"
                                @input="updateForm('width', ($event.target as HTMLInputElement).value)"
                                type="number"
                                min="100"
                                max="4000"
                                step="1"
                                placeholder="Width"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <div>
                            <input
                                :value="form.height"
                                @input="updateForm('height', ($event.target as HTMLInputElement).value)"
                                type="number"
                                min="100"
                                max="4000"
                                step="1"
                                placeholder="Height"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                </div>

                <!-- Create/Update Template Button -->
                <div class="mt-4 flex justify-start">
                    <button
                        type="submit"
                        :disabled="!form.name || isSubmitting"
                        class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
                    >
                        <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                        <span>
                            {{ isSubmitting 
                                ? (isEditMode ? 'Updating...' : 'Creating...') 
                                : (isEditMode ? 'Update Template' : 'Create Template') 
                            }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
