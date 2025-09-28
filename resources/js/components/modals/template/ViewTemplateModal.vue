<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="background-color: rgba(0, 0, 0, 0.4);"
        @click="closeModal"
    >
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden"
            @click.stop
        >
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ template?.name }}
                    </h3>
                    <p v-if="template?.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ template.description }}
                    </p>
                </div>
                <button
                    @click="closeModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-auto max-h-[calc(90vh-120px)]">
                <div class="space-y-6">
                    <!-- Template Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Dimensions</h4>
                            <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                {{ template?.width }} × {{ template?.height }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Status</h4>
                            <span
                                :class="template?.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{ template?.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Created</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ template?.created_at ? new Date(template.created_at).toLocaleDateString() : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <!-- Canvas Preview -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">Template Preview</h4>
                        <div class="flex justify-center">
                            <div
                                class="relative border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden shadow-lg"
                                :style="{
                                    width: `${template?.width || 800}px`,
                                    height: `${template?.height || 600}px`,
                                    transform: `scale(${getCanvasScale()})`,
                                    transformOrigin: 'top center'
                                }"
                            >
                                <!-- Background Image -->
                                <div
                                    v-if="template?.background_image"
                                    class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                                    :style="{
                                        backgroundImage: `url('/storage/${template.background_image}')`
                                    }"
                                ></div>
                                <div
                                    v-else
                                    class="absolute inset-0 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-gray-700 dark:to-gray-800"
                                ></div>

                                <!-- Canvas Elements -->
                                <div
                                    v-for="element in canvasElements"
                                    :key="element.id"
                                    class="absolute"
                                    :style="{
                                        left: `${element.x}px`,
                                        top: `${element.y}px`,
                                        width: `${element.width}px`,
                                        height: `${element.height}px`,
                                        transform: `rotate(${element.rotation}deg)`,
                                        zIndex: element.zIndex
                                    }"
                                >
                                    <!-- Text Element -->
                                    <div
                                        v-if="element.type === 'text'"
                                        class="w-full h-full flex items-center"
                                        :style="{
                                            fontSize: `${element.properties.fontSize || 16}px`,
                                            fontFamily: element.properties.fontFamily || 'Arial',
                                            fontWeight: element.properties.fontWeight || 'normal',
                                            fontStyle: element.properties.fontStyle || 'normal',
                                            textDecoration: element.properties.textDecoration || 'none',
                                            textAlign: element.properties.textAlign || 'left',
                                            lineHeight: element.properties.lineHeight || 1.2,
                                            color: element.properties.color || '#000000',
                                            backgroundColor: element.properties.backgroundColor || 'transparent',
                                            textShadow: element.properties.textShadow || 'none',
                                            border: element.properties.hasBorder ? `${element.properties.borderWidth || 1}px ${element.properties.borderStyle || 'solid'} ${element.properties.borderColor || '#000000'}` : 'none',
                                            borderRadius: `${element.properties.borderRadius || 0}px`,
                                            boxShadow: element.properties.boxShadow || 'none',
                                            padding: '4px'
                                        }"
                                    >
                                        {{ element.properties.text || 'Sample Text' }}
                                    </div>

                                    <!-- Image Element -->
                                    <div
                                        v-else-if="element.type === 'image'"
                                        class="w-full h-full"
                                        :style="{
                                            backgroundColor: element.properties.backgroundColor || 'transparent',
                                            border: element.properties.hasBorder ? `${element.properties.borderWidth || 1}px ${element.properties.borderStyle || 'solid'} ${element.properties.borderColor || '#000000'}` : 'none',
                                            borderRadius: `${element.properties.borderRadius || 0}px`,
                                            boxShadow: element.properties.boxShadow || 'none',
                                            overflow: 'hidden'
                                        }"
                                    >
                                        <img
                                            v-if="element.properties.imageUrl"
                                            :src="element.properties.imageUrl"
                                            :alt="element.properties.text || 'Image'"
                                            class="w-full h-full"
                                            :style="{
                                                objectFit: element.properties.imageFit || 'contain'
                                            }"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-400 text-sm"
                                        >
                                            No Image
                                        </div>
                                    </div>

                                    <!-- Shape Elements -->
                                    <div
                                        v-else
                                        class="w-full h-full"
                                        :style="{
                                            backgroundColor: element.properties.fillColor || '#000000',
                                            border: element.properties.hasBorder ? `${element.properties.borderWidth || 1}px ${element.properties.borderStyle || 'solid'} ${element.properties.borderColor || '#000000'}` : 'none',
                                            borderRadius: element.type === 'circle' ? '50%' : `${element.properties.borderRadius || 0}px`,
                                            boxShadow: element.properties.boxShadow || 'none'
                                        }"
                                        :class="{
                                            'rounded-none': element.type === 'rectangle' || element.type === 'triangle' || element.type === 'star',
                                            'rounded-full': element.type === 'circle'
                                        }"
                                    >
                                        <!-- Triangle Shape -->
                                        <div
                                            v-if="element.type === 'triangle'"
                                            class="w-full h-full"
                                            :style="{
                                                clipPath: 'polygon(50% 0%, 0% 100%, 100% 100%)',
                                                backgroundColor: element.properties.fillColor || '#000000'
                                            }"
                                        ></div>
                                        
                                        <!-- Star Shape -->
                                        <div
                                            v-else-if="element.type === 'star'"
                                            class="w-full h-full flex items-center justify-center text-white font-bold"
                                            :style="{
                                                fontSize: `${Math.min(element.width, element.height) * 0.3}px`
                                            }"
                                        >
                                            ★
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Elements List -->
                    <div v-if="canvasElements.length > 0" class="space-y-4">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">Elements ({{ canvasElements.length }})</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="(element, index) in canvasElements"
                                :key="element.id"
                                class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ element.type.charAt(0).toUpperCase() + element.type.slice(1) }} {{ index + 1 }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ element.width }}×{{ element.height }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                    <div>Position: {{ Math.round(element.x) }}, {{ Math.round(element.y) }}</div>
                                    <div v-if="element.type === 'text'">Text: "{{ element.properties.text || 'Sample Text' }}"</div>
                                    <div v-if="element.type === 'image'">Image: {{ element.properties.imageUrl ? 'Loaded' : 'No Image' }}</div>
                                    <div>Layer: {{ element.zIndex }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Elements Message -->
                    <div v-else class="text-center py-8">
                        <div class="text-gray-400 dark:text-gray-500 mb-2">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">No elements added to this template</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
                <button
                    @click="closeModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface CanvasElement {
    id: string
    type: 'text' | 'image' | 'rectangle' | 'circle' | 'triangle' | 'star'
    x: number
    y: number
    width: number
    height: number
    rotation: number
    zIndex: number
    properties: {
        text?: string
        fontSize?: number
        fontFamily?: string
        fontWeight?: string
        fontStyle?: string
        textDecoration?: string
        textAlign?: string
        lineHeight?: number
        color?: string
        fillColor?: string
        strokeColor?: string
        strokeWidth?: number
        hasBorder?: boolean
        borderWidth?: number
        borderStyle?: string
        borderColor?: string
        borderRadius?: number
        boxShadow?: string
        textShadow?: string
        imageUrl?: string
        imageFit?: string
        backgroundColor?: string
        imageShape?: string
    }
}

interface Template {
    id: number
    name: string
    description?: string
    width: number
    height: number
    background_image?: string
    canvas_data: CanvasElement[] | string
    share_token: string
    is_active: boolean
    created_at: string
    updated_at: string
}

const props = defineProps<{
    isOpen: boolean
    template: Template | null
}>()

const emit = defineEmits<{
    close: []
}>()

const canvasElements = computed(() => {
    if (!props.template?.canvas_data) return []
    
    let canvasData = props.template.canvas_data
    
    // If canvas_data is a string, parse it as JSON
    if (typeof canvasData === 'string') {
        try {
            canvasData = JSON.parse(canvasData)
        } catch (error) {
            console.error('Error parsing canvas_data:', error)
            return []
        }
    }
    
    // Ensure it's an array
    if (!Array.isArray(canvasData)) return []
    
    return [...canvasData].sort((a, b) => a.zIndex - b.zIndex)
})

const getCanvasScale = () => {
    if (!props.template) return 1
    
    const maxWidth = 800
    const maxHeight = 600
    const scaleX = maxWidth / props.template.width
    const scaleY = maxHeight / props.template.height
    
    // Use the smaller scale to ensure the canvas fits within the modal
    const scale = Math.min(scaleX, scaleY)
    
    // Don't scale up if the canvas is smaller than the max dimensions
    return Math.min(scale, 1)
}

const closeModal = () => {
    emit('close')
}
</script>
