<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const settings = computed(() => props.block.settings || {})

const spacerStyle = computed(() => ({
    height: (settings.value.height || 50) + 'px'
}))
</script>

<template>
    <div class="spacer-block" :style="spacerStyle">
        <hr v-if="settings.show_divider" class="border-gray-200 mx-auto" :style="{ maxWidth: (settings.divider_width || 100) + '%' }">
    </div>
</template>
