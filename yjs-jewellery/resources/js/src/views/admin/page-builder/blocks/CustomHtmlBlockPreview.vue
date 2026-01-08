<script setup>
import { computed } from 'vue'

const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}

const hasContent = computed(() => {
    return data.html || data.css || data.js
})
</script>

<template>
    <div class="border border-dashed border-gray-300 rounded-lg p-4">
        <div v-if="hasContent">
            <!-- CSS -->
            <style v-if="data.css" scoped>{{ data.css }}</style>

            <!-- HTML Preview -->
            <div
                v-if="data.html"
                v-html="data.html"
                :class="{ 'sandbox-content': settings.sandbox }"
            ></div>

            <!-- JS Notice -->
            <p v-if="data.js" class="text-xs text-amber-600 mt-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                JavaScript is disabled in preview mode
            </p>
        </div>

        <div v-else class="text-center py-8 text-gray-400">
            <i class="bi bi-code-slash text-3xl"></i>
            <p class="mt-2">Custom HTML Block</p>
            <p class="text-sm">Click to add HTML, CSS, or JavaScript</p>
        </div>
    </div>
</template>

<style scoped>
.sandbox-content {
    /* Prevent potentially dangerous styles */
    position: relative !important;
}
</style>
