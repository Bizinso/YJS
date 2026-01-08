<script setup>
import { computed } from 'vue'

const props = defineProps({
    block: { type: Object, required: true }
})

const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})

const updateData = (key, value) => {
    emit('update:data', { [key]: value })
}
</script>

<template>
    <div class="space-y-4">
        <!-- Height -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Height: {{ data.height || 40 }}px
            </label>
            <input
                type="range"
                :value="data.height || 40"
                @input="updateData('height', parseInt($event.target.value))"
                min="10"
                max="200"
                step="5"
                class="w-full"
            >
            <div class="flex justify-between text-xs text-gray-400">
                <span>10px</span>
                <span>200px</span>
            </div>
        </div>

        <!-- Show Divider -->
        <div>
            <label class="flex items-center">
                <input
                    type="checkbox"
                    :checked="data.show_divider"
                    @change="updateData('show_divider', $event.target.checked)"
                    class="rounded border-gray-300 text-amber-600"
                >
                <span class="ml-2 text-sm text-gray-700">Show Divider Line</span>
            </label>
        </div>

        <!-- Divider Options -->
        <div v-if="data.show_divider" class="space-y-4 pl-4 border-l-2 border-amber-200">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Divider Style</label>
                <select
                    :value="data.divider_style"
                    @change="updateData('divider_style', $event.target.value)"
                    class="w-full px-3 py-2 border rounded-lg text-sm"
                >
                    <option value="solid">Solid</option>
                    <option value="dashed">Dashed</option>
                    <option value="dotted">Dotted</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Divider Color</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="data.divider_color || '#e0e0e0'"
                        @input="updateData('divider_color', $event.target.value)"
                        class="w-10 h-10 rounded border cursor-pointer"
                    >
                    <input
                        type="text"
                        :value="data.divider_color"
                        @input="updateData('divider_color', $event.target.value)"
                        placeholder="#e0e0e0"
                        class="flex-1 px-3 py-2 border rounded-lg text-sm"
                    >
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Divider Width</label>
                <input
                    type="text"
                    :value="data.divider_width"
                    @input="updateData('divider_width', $event.target.value)"
                    placeholder="100%"
                    class="w-full px-3 py-2 border rounded-lg text-sm"
                >
            </div>
        </div>
    </div>
</template>
