<script setup>
import { computed } from 'vue'
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <div class="p-4">
        <div class="flex items-center">
            <img
                v-if="block.data?.image_url"
                :src="block.data.image_url"
                :alt="block.data?.alt || 'Logo'"
                :style="{ maxWidth: (block.settings?.max_width || 150) + 'px' }"
                class="h-auto object-contain"
            >
            <div v-else class="w-32 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400">
                <i class="bi bi-image text-2xl"></i>
            </div>
        </div>
    </div>
</template>
