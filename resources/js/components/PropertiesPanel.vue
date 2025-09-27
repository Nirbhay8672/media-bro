<script setup lang="ts">
import { ref } from 'vue';
import { Layers, Copy, Trash2 } from 'lucide-vue-next';

interface CanvasElement {
    id: string;
    type: 'text' | 'image' | 'rectangle' | 'circle' | 'triangle' | 'star' | 'heart';
    x: number;
    y: number;
    width: number;
    height: number;
    rotation: number;
    zIndex: number;
    properties: {
        text?: string;
        fontSize?: number;
        fontFamily?: string;
        fontWeight?: string;
        fontStyle?: string;
        textDecoration?: string;
        textAlign?: string;
        lineHeight?: number;
        color?: string;
        fillColor?: string;
        strokeColor?: string;
        strokeWidth?: number;
        hasBorder?: boolean;
        borderWidth?: number;
        borderStyle?: string;
        borderColor?: string;
        borderRadius?: number;
        boxShadow?: string;
        textShadow?: string;
        imageUrl?: string;
        imageFit?: string;
        backgroundColor?: string;
    };
}

interface Props {
    selectedElement: CanvasElement | null;
}

interface Emits {
    (e: 'updateElement', elementId: string, property: keyof CanvasElement['properties'], value: any): void;
    (e: 'bringToFront', elementId: string): void;
    (e: 'sendToBack', elementId: string): void;
    (e: 'duplicateElement', elementId: string): void;
    (e: 'deleteElement', elementId: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const activePropertyTab = ref<'content' | 'style' | 'position'>('content');

// Font families
const fontFamilies = [
    'Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana', 'Courier New',
    'Impact', 'Comic Sans MS', 'Trebuchet MS', 'Arial Black', 'Palatino',
    'Garamond', 'Bookman', 'Avant Garde', 'Helvetica Neue', 'Roboto',
    'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Source Sans Pro'
];

// Shadow presets
const shadowPresets = [
    { name: 'None', value: 'none' },
    { name: 'Small', value: 'drop-shadow(0 2px 4px rgba(0,0,0,0.1))' },
    { name: 'Medium', value: 'drop-shadow(0 4px 8px rgba(0,0,0,0.15))' },
    { name: 'Large', value: 'drop-shadow(0 8px 16px rgba(0,0,0,0.2))' },
    { name: 'X-Large', value: 'drop-shadow(0 12px 24px rgba(0,0,0,0.25))' },
    { name: 'Glow', value: 'drop-shadow(0 0 20px rgba(0,0,0,0.3))' }
];

// Border styles
const borderStyles = [
    { name: 'Solid', value: 'solid' },
    { name: 'Dashed', value: 'dashed' },
    { name: 'Dotted', value: 'dotted' },
    { name: 'Double', value: 'double' },
    { name: 'Groove', value: 'groove' },
    { name: 'Ridge', value: 'ridge' },
    { name: 'Inset', value: 'inset' },
    { name: 'Outset', value: 'outset' }
];

// Color palette
const colorPalette = [
    '#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00',
    '#FF00FF', '#00FFFF', '#FFA500', '#800080', '#FFC0CB', '#A52A2A',
    '#808080', '#C0C0C0', '#FFD700', '#008000', '#000080', '#800000'
];

const updateElementProperty = (property: string, value: any) => {
    if (props.selectedElement) {
        emit('updateElement', props.selectedElement.id, property, value);
    }
};

const getInputValue = (event: Event): string => {
    const target = event.target as HTMLInputElement;
    return target?.value || '';
};

const getCheckboxChecked = (event: Event): boolean => {
    const target = event.target as HTMLInputElement;
    return target?.checked || false;
};
</script>

<template>
    <div v-if="selectedElement" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Properties</h2>
            
            <!-- Properties Tabs -->
            <div class="mb-4">
                <div class="flex space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button
                        type="button"
                        @click="activePropertyTab = 'content'"
                        :class="[
                            'flex-1 px-3 py-2 text-xs font-medium rounded-md transition-colors',
                            activePropertyTab === 'content' 
                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' 
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        Content
                    </button>
                    <button
                        type="button"
                        @click="activePropertyTab = 'style'"
                        :class="[
                            'flex-1 px-3 py-2 text-xs font-medium rounded-md transition-colors',
                            activePropertyTab === 'style' 
                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' 
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        Style
                    </button>
                    <button
                        type="button"
                        @click="activePropertyTab = 'position'"
                        :class="[
                            'flex-1 px-3 py-2 text-xs font-medium rounded-md transition-colors',
                            activePropertyTab === 'position' 
                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' 
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        Position
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                <!-- Content Tab -->
                <div v-if="activePropertyTab === 'content'" class="space-y-2">
                    <!-- Text Content (only for text elements) -->
                    <div v-if="selectedElement.type === 'text'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Text Content
                        </label>
                        <textarea
                            :value="selectedElement.properties.text || ''"
                            @input="updateElementProperty('text', getInputValue($event))"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors text-sm"
                            placeholder="Enter text content"
                        ></textarea>
                    </div>

                    <!-- Image URL (only for image elements) -->
                    <div v-if="selectedElement.type === 'image'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Image URL
                        </label>
                        <input
                            :value="selectedElement.properties.imageUrl || ''"
                            @input="updateElementProperty('imageUrl', getInputValue($event))"
                            type="url"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors text-sm"
                            placeholder="Enter image URL"
                        />
                    </div>
                </div>

                <!-- Style Tab -->
                <div v-if="activePropertyTab === 'style'" class="space-y-2">
                    <!-- Text Styles (only for text elements) -->
                    <div v-if="selectedElement.type === 'text'" class="space-y-2">
                        <!-- Font Size -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Font Size
                            </label>
                            <input
                                :value="selectedElement.properties.fontSize || 16"
                                @input="updateElementProperty('fontSize', parseInt(getInputValue($event)) || 16)"
                                type="number"
                                min="8"
                                max="200"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Font Family -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Font Family
                            </label>
                            <select
                                :value="selectedElement.properties.fontFamily || 'Arial'"
                                @change="updateElementProperty('fontFamily', getInputValue($event))"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="font in fontFamilies" :key="font" :value="font">{{ font }}</option>
                            </select>
                        </div>

                        <!-- Font Style Buttons -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Font Style
                            </label>
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    @click="updateElementProperty('fontWeight', selectedElement.properties.fontWeight === 'bold' ? 'normal' : 'bold')"
                                    :class="[
                                        'px-2 py-1 text-xs rounded',
                                        selectedElement.properties.fontWeight === 'bold'
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    Bold
                                </button>
                                <button
                                    type="button"
                                    @click="updateElementProperty('fontStyle', selectedElement.properties.fontStyle === 'italic' ? 'normal' : 'italic')"
                                    :class="[
                                        'px-2 py-1 text-xs rounded',
                                        selectedElement.properties.fontStyle === 'italic'
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    Italic
                                </button>
                                <button
                                    type="button"
                                    @click="updateElementProperty('textDecoration', selectedElement.properties.textDecoration === 'underline' ? 'none' : 'underline')"
                                    :class="[
                                        'px-2 py-1 text-xs rounded',
                                        selectedElement.properties.textDecoration === 'underline'
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    Underline
                                </button>
                            </div>
                        </div>

                        <!-- Text Alignment -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Text Alignment
                            </label>
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    @click="updateElementProperty('textAlign', 'left')"
                                    :class="[
                                        'px-2 py-1 text-xs rounded',
                                        selectedElement.properties.textAlign === 'left'
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    Left
                                </button>
                                <button
                                    type="button"
                                    @click="updateElementProperty('textAlign', 'center')"
                                    :class="[
                                        'px-2 py-1 text-xs rounded',
                                        selectedElement.properties.textAlign === 'center'
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    Center
                                </button>
                                <button
                                    type="button"
                                    @click="updateElementProperty('textAlign', 'right')"
                                    :class="[
                                        'px-2 py-1 text-xs rounded',
                                        selectedElement.properties.textAlign === 'right'
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    Right
                                </button>
                            </div>
                        </div>

                        <!-- Text Color -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Text Color
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    :value="selectedElement.properties.color || '#000000'"
                                    @input="updateElementProperty('color', getInputValue($event))"
                                    type="color"
                                    class="w-8 h-8 border border-gray-300 dark:border-gray-600 rounded cursor-pointer"
                                />
                                <div class="flex flex-wrap gap-1">
                                    <button
                                        v-for="color in colorPalette"
                                        :key="color"
                                        type="button"
                                        @click="updateElementProperty('color', color)"
                                        :style="{ backgroundColor: color }"
                                        class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                                    ></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Styles (only for image elements) -->
                    <div v-if="selectedElement.type === 'image'" class="space-y-2">
                        <!-- Image Fit -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Image Fit
                            </label>
                            <select
                                :value="selectedElement.properties.imageFit || 'contain'"
                                @change="updateElementProperty('imageFit', getInputValue($event))"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="contain">Contain</option>
                                <option value="cover">Cover</option>
                                <option value="fill">Fill</option>
                                <option value="scale-down">Scale Down</option>
                            </select>
                        </div>

                        <!-- Background Color -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Background
                            </label>
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <label class="flex items-center">
                                        <input
                                            type="radio"
                                            :checked="!selectedElement.properties.backgroundColor || selectedElement.properties.backgroundColor === 'transparent'"
                                            @change="updateElementProperty('backgroundColor', 'transparent')"
                                            class="mr-1"
                                        />
                                        <span class="text-xs">Transparent</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="radio"
                                            :checked="selectedElement.properties.backgroundColor && selectedElement.properties.backgroundColor !== 'transparent'"
                                            @change="updateElementProperty('backgroundColor', '#ffffff')"
                                            class="mr-1"
                                        />
                                        <span class="text-xs">Background Color</span>
                                    </label>
                                </div>
                                <div v-if="selectedElement.properties.backgroundColor && selectedElement.properties.backgroundColor !== 'transparent'" class="flex items-center gap-2">
                                    <input
                                        :value="selectedElement.properties.backgroundColor"
                                        @input="updateElementProperty('backgroundColor', getInputValue($event))"
                                        type="color"
                                        class="w-8 h-8 border border-gray-300 dark:border-gray-600 rounded cursor-pointer"
                                    />
                                    <div class="flex flex-wrap gap-1">
                                        <button
                                            v-for="color in colorPalette"
                                            :key="color"
                                            type="button"
                                            @click="updateElementProperty('backgroundColor', color)"
                                            :style="{ backgroundColor: color }"
                                            class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                                        ></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fill Color (for shapes) -->
                    <div v-if="['rectangle', 'circle', 'triangle', 'star', 'heart'].includes(selectedElement.type)">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Fill Color
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="selectedElement.properties.fillColor || '#000000'"
                                @input="updateElementProperty('fillColor', getInputValue($event))"
                                type="color"
                                class="w-8 h-8 border border-gray-300 dark:border-gray-600 rounded cursor-pointer"
                            />
                            <div class="flex flex-wrap gap-1">
                                <button
                                    v-for="color in colorPalette"
                                    :key="color"
                                    type="button"
                                    @click="updateElementProperty('fillColor', color)"
                                    :style="{ backgroundColor: color }"
                                    class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                                ></button>
                            </div>
                        </div>
                    </div>

                    <!-- Stroke Color -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Stroke Color
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                :value="selectedElement.properties.strokeColor || '#000000'"
                                @input="updateElementProperty('strokeColor', getInputValue($event))"
                                type="color"
                                class="w-8 h-8 border border-gray-300 dark:border-gray-600 rounded cursor-pointer"
                            />
                            <div class="flex flex-wrap gap-1">
                                <button
                                    v-for="color in colorPalette"
                                    :key="color"
                                    type="button"
                                    @click="updateElementProperty('strokeColor', color)"
                                    :style="{ backgroundColor: color }"
                                    class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                                ></button>
                            </div>
                        </div>
                    </div>

                    <!-- Border Settings -->
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <input
                                :checked="selectedElement.properties.hasBorder || false"
                                @change="updateElementProperty('hasBorder', getCheckboxChecked($event))"
                                type="checkbox"
                                class="rounded"
                            />
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Enable Border
                            </label>
                        </div>
                        <div v-if="selectedElement.properties.hasBorder" class="space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        Width
                                    </label>
                                    <input
                                        :value="selectedElement.properties.borderWidth || 1"
                                        @input="updateElementProperty('borderWidth', parseInt(getInputValue($event)) || 1)"
                                        type="number"
                                        min="1"
                                        max="20"
                                        class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        Style
                                    </label>
                                    <select
                                        :value="selectedElement.properties.borderStyle || 'solid'"
                                        @change="updateElementProperty('borderStyle', getInputValue($event))"
                                        class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option v-for="style in borderStyles" :key="style.value" :value="style.value">{{ style.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    Border Radius
                                </label>
                                <input
                                    :value="selectedElement.properties.borderRadius || 0"
                                    @input="updateElementProperty('borderRadius', parseInt(getInputValue($event)) || 0)"
                                    type="number"
                                    min="0"
                                    max="50"
                                    class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Shadow -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Shadow
                        </label>
                        <select
                            :value="selectedElement.properties.boxShadow || 'none'"
                            @change="updateElementProperty('boxShadow', getInputValue($event))"
                            class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="preset in shadowPresets" :key="preset.value" :value="preset.value">{{ preset.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Position Tab -->
                <div v-if="activePropertyTab === 'position'" class="space-y-2">
                    <!-- Position -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                X Position
                            </label>
                            <input
                                :value="selectedElement.x"
                                @input="updateElementProperty('x', parseInt(getInputValue($event)) || 0)"
                                type="number"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Y Position
                            </label>
                            <input
                                :value="selectedElement.y"
                                @input="updateElementProperty('y', parseInt(getInputValue($event)) || 0)"
                                type="number"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- Size -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Width
                            </label>
                            <input
                                :value="selectedElement.width"
                                @input="updateElementProperty('width', parseFloat(getInputValue($event)) || 10)"
                                type="number"
                                step="0.5"
                                min="10"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Height
                            </label>
                            <input
                                :value="selectedElement.height"
                                @input="updateElementProperty('height', parseFloat(getInputValue($event)) || 10)"
                                type="number"
                                step="0.5"
                                min="10"
                                class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- Layer Controls -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Layer</label>
                        <div class="grid grid-cols-2 gap-1">
                            <button
                                type="button"
                                @click="emit('bringToFront', selectedElement.id)"
                                class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                <Layers class="h-3 w-3 mx-auto" />
                            </button>
                            <button
                                type="button"
                                @click="emit('sendToBack', selectedElement.id)"
                                class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                <Layers class="h-3 w-3 mx-auto rotate-180" />
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2">
                        <button
                            type="button"
                            @click="emit('duplicateElement', selectedElement.id)"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
                        >
                            <Copy class="h-4 w-4" />
                            Duplicate
                        </button>
                        <button
                            type="button"
                            @click="emit('deleteElement', selectedElement.id)"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm"
                        >
                            <Trash2 class="h-4 w-4" />
                            Delete Element
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
