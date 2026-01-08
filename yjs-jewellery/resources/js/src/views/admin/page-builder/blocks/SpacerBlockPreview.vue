<script setup>
const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
</script>

<template>
    <div
        :style="{ height: `${data.height || 40}px` }"
        class="relative flex items-center justify-center"
    >
        <!-- Divider line -->
        <div
            v-if="data.show_divider"
            :style="{
                width: data.divider_width || '100%',
                borderTopStyle: data.divider_style || 'solid',
                borderTopColor: data.divider_color || '#e0e0e0',
                borderTopWidth: '1px'
            }"
        ></div>

        <!-- Height indicator (only in builder) -->
        <span class="absolute text-xs text-gray-400 bg-white px-2">
            {{ data.height || 40 }}px
        </span>
    </div>
</template>
