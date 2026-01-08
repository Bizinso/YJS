<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const iconMap = {
    facebook: 'bi-facebook',
    instagram: 'bi-instagram',
    twitter: 'bi-twitter-x',
    youtube: 'bi-youtube',
    linkedin: 'bi-linkedin',
    pinterest: 'bi-pinterest',
    whatsapp: 'bi-whatsapp',
    tiktok: 'bi-tiktok'
}

const sizeClasses = {
    small: 'text-lg w-8 h-8',
    medium: 'text-xl w-10 h-10',
    large: 'text-2xl w-12 h-12'
}
</script>

<template>
    <div class="p-4 flex items-center justify-center gap-3">
        <a
            v-for="(icon, i) in (block.data?.icons || [])"
            :key="i"
            href="#"
            :class="[
                'flex items-center justify-center rounded-full transition-colors',
                sizeClasses[block.settings?.size || 'medium'],
                block.settings?.style === 'filled' ? 'bg-gray-800 text-white hover:bg-amber-600' : 'text-gray-600 hover:text-amber-600 border border-gray-300 hover:border-amber-600'
            ]"
        >
            <i :class="iconMap[icon.platform] || 'bi-link-45deg'"></i>
        </a>
        <div v-if="!block.data?.icons?.length" class="text-gray-400 text-sm">
            Add social icons in the editor
        </div>
    </div>
</template>
