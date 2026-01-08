<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const displayText = computed(() => {
    const text = props.block.data?.text || '© {year} Company. All rights reserved.'
    return text.replace('{year}', new Date().getFullYear())
})
</script>

<template>
    <div class="p-4 border-t bg-gray-50">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-gray-600">
            <p>{{ displayText }}</p>
            <div v-if="block.data?.links?.length" class="flex items-center gap-4">
                <a
                    v-for="(link, i) in block.data.links"
                    :key="i"
                    href="#"
                    class="hover:text-amber-600"
                >
                    {{ link.label }}
                </a>
            </div>
        </div>
    </div>
</template>
