<script setup>
import { computed } from 'vue'
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <div
        class="py-2 px-4 text-center text-sm"
        :style="{
            backgroundColor: block.settings?.background || '#1a1a1a',
            color: block.settings?.text_color || '#ffffff'
        }"
    >
        <p v-if="block.data?.messages?.length">
            {{ block.data.messages[0]?.text || 'Add announcement message' }}
        </p>
        <p v-else class="opacity-60">Add announcement message</p>
    </div>
</template>
