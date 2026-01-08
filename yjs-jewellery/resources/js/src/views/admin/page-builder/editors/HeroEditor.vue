<script setup>
import { computed } from 'vue'

const props = defineProps({
    block: { type: Object, required: true }
})

const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const updateData = (key, value) => {
    emit('update:data', { [key]: value })
}

const updateSettings = (key, value) => {
    emit('update:settings', { [key]: value })
}
</script>

<template>
    <div class="space-y-4">
        <!-- Title -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
            <input
                type="text"
                :value="data.title"
                @input="updateData('title', $event.target.value)"
                placeholder="Hero title"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
        </div>

        <!-- Subtitle -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
            <textarea
                :value="data.subtitle"
                @input="updateData('subtitle', $event.target.value)"
                placeholder="Hero subtitle"
                rows="2"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            ></textarea>
        </div>

        <!-- Background Image -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Background Image URL</label>
            <input
                type="text"
                :value="data.background_image"
                @input="updateData('background_image', $event.target.value)"
                placeholder="https://example.com/image.jpg"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
        </div>

        <!-- Overlay Color -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Overlay Color</label>
            <input
                type="text"
                :value="data.overlay_color"
                @input="updateData('overlay_color', $event.target.value)"
                placeholder="rgba(0,0,0,0.4)"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
        </div>

        <!-- Text Color -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Text Color</label>
            <div class="flex items-center space-x-2">
                <input
                    type="color"
                    :value="data.text_color || '#ffffff'"
                    @input="updateData('text_color', $event.target.value)"
                    class="w-10 h-10 rounded border cursor-pointer"
                >
                <input
                    type="text"
                    :value="data.text_color"
                    @input="updateData('text_color', $event.target.value)"
                    placeholder="#ffffff"
                    class="flex-1 px-3 py-2 border rounded-lg text-sm"
                >
            </div>
        </div>

        <!-- CTA Button -->
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
                <input
                    type="text"
                    :value="data.cta_text"
                    @input="updateData('cta_text', $event.target.value)"
                    placeholder="Shop Now"
                    class="w-full px-3 py-2 border rounded-lg text-sm"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Button Link</label>
                <input
                    type="text"
                    :value="data.cta_link"
                    @input="updateData('cta_link', $event.target.value)"
                    placeholder="/products"
                    class="w-full px-3 py-2 border rounded-lg text-sm"
                >
            </div>
        </div>

        <!-- CTA Style -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Button Style</label>
            <select
                :value="data.cta_style"
                @change="updateData('cta_style', $event.target.value)"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
                <option value="primary">Primary (Filled)</option>
                <option value="secondary">Secondary</option>
                <option value="outline">Outline</option>
            </select>
        </div>

        <!-- Alignment -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Text Alignment</label>
            <div class="flex space-x-2">
                <button
                    v-for="align in ['left', 'center', 'right']"
                    :key="align"
                    @click="updateData('alignment', align)"
                    :class="[
                        'flex-1 py-2 border rounded-lg text-sm capitalize',
                        data.alignment === align ? 'bg-amber-100 border-amber-500 text-amber-700' : 'hover:bg-gray-50'
                    ]"
                >
                    {{ align }}
                </button>
            </div>
        </div>

        <!-- Height -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Height</label>
            <input
                type="text"
                :value="data.height"
                @input="updateData('height', $event.target.value)"
                placeholder="500px"
                class="w-full px-3 py-2 border rounded-lg text-sm"
            >
        </div>

        <!-- Settings -->
        <div class="pt-4 border-t space-y-2">
            <label class="flex items-center">
                <input
                    type="checkbox"
                    :checked="settings.full_width"
                    @change="updateSettings('full_width', $event.target.checked)"
                    class="rounded border-gray-300 text-amber-600"
                >
                <span class="ml-2 text-sm text-gray-700">Full Width</span>
            </label>
            <label class="flex items-center">
                <input
                    type="checkbox"
                    :checked="settings.parallax"
                    @change="updateSettings('parallax', $event.target.checked)"
                    class="rounded border-gray-300 text-amber-600"
                >
                <span class="ml-2 text-sm text-gray-700">Parallax Effect</span>
            </label>
        </div>
    </div>
</template>
