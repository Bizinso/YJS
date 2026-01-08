<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update:data', 'update:settings'])

const settings = computed(() => props.block.settings || {})

const updateSettings = (key, value) => emit('update:settings', { [key]: value })
</script>

<template>
    <div class="space-y-4">
        <p class="text-sm text-gray-600">Cart icon displays the shopping cart with item count badge. It links to the cart page.</p>

        <div class="pt-4 border-t space-y-3">
            <label class="flex items-center">
                <input type="checkbox" :checked="settings.show_count !== false" @change="updateSettings('show_count', $event.target.checked)" class="rounded border-gray-300 text-amber-600">
                <span class="ml-2 text-sm">Show Item Count Badge</span>
            </label>

            <label class="flex items-center">
                <input type="checkbox" :checked="settings.show_total" @change="updateSettings('show_total', $event.target.checked)" class="rounded border-gray-300 text-amber-600">
                <span class="ml-2 text-sm">Show Cart Total</span>
            </label>
        </div>
    </div>
</template>
