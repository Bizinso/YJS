<script setup>
import { computed, onMounted, ref } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})
const containerRef = ref(null)

// Execute any scripts in the custom HTML after mounting
onMounted(() => {
    if (settings.value.execute_scripts && containerRef.value) {
        const scripts = containerRef.value.querySelectorAll('script')
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script')
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value)
            })
            newScript.textContent = oldScript.textContent
            oldScript.parentNode.replaceChild(newScript, oldScript)
        })
    }
})

const containerClass = computed(() => {
    const classes = []
    if (settings.value.container === 'contained') {
        classes.push('container mx-auto px-4')
    }
    if (settings.value.padding) {
        classes.push(`py-${settings.value.padding}`)
    }
    return classes.join(' ')
})
</script>

<template>
    <section class="custom-html-block">
        <div :class="containerClass" ref="containerRef" v-html="data.html_content"></div>
    </section>
</template>

<style scoped>
.custom-html-block :deep(*) {
    /* Allow custom HTML to override styles */
}
</style>
