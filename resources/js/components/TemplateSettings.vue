<script setup lang="ts">
import { ref, computed } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

interface Props {
    form: {
        name: string;
        description: string;
        width: number;
        height: number;
        background_image: File | null;
    };
    backgroundImagePreview: string | null;
    selectedQuickTemplate: { name: string; width: number; height: number } | null;
    isQuickTemplateDropdownOpen: boolean;
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

// Quick templates
const quickTemplates = [
    { name: 'Social Media Post', width: 1080, height: 1080 },
    { name: 'Instagram Story', width: 1080, height: 1920 },
    { name: 'Facebook Cover', width: 1200, height: 630 },
    { name: 'Twitter Header', width: 1500, height: 500 },
    { name: 'YouTube Thumbnail', width: 1280, height: 720 },
    { name: 'Business Card', width: 1050, height: 600 },
    { name: 'A4 Document', width: 2480, height: 3508 },
    { name: 'Custom', width: 800, height: 600 }
];

const updateForm = (field: string, value: any) => {
    emit('update:form', { ...props.form, [field]: value });
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
    <div class="w-72 flex-shrink-0">
        <div class="h-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Template Settings</h2>
            </div>
            <div class="h-[calc(100%-80px)] p-4 space-y-3 overflow-y-auto">
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

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea
                        :value="form.description"
                        @input="updateForm('description', ($event.target as HTMLTextAreaElement).value)"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors resize-none text-sm"
                        placeholder="Describe your template"
                    ></textarea>
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
                            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
                        >
                            <button
                                v-for="template in quickTemplates"
                                :key="template.name"
                                type="button"
                                @click="applyQuickTemplate(template)"
                                class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 text-sm"
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Canvas Size
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                Width
                            </label>
                            <input
                                :value="form.width"
                                @input="updateForm('width', parseInt(($event.target as HTMLInputElement).value) || 100)"
                                type="number"
                                min="100"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                Height
                            </label>
                            <input
                                :value="form.height"
                                @input="updateForm('height', parseInt(($event.target as HTMLInputElement).value) || 100)"
                                type="number"
                                min="100"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Background -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Background
                    </label>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleBackgroundImageChange"
                            class="hidden"
                            id="background-upload"
                        />
                        <label for="background-upload" class="cursor-pointer">
                            <Upload class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ backgroundImagePreview ? 'Change Background' : 'Upload Image' }}
                            </p>
                        </label>
                        <div v-if="backgroundImagePreview" class="mt-2">
                            <img :src="backgroundImagePreview" alt="Background preview" class="mx-auto h-16 w-16 object-cover rounded" />
                        </div>
                    </div>
                </div>

                <!-- Create Template Button -->
                <button
                    type="submit"
                    :disabled="!form.name"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    Create Template
                </button>
            </div>
        </div>
    </div>
</template>
