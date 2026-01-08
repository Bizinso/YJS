<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <div class="p-4">
        <a href="#" class="relative inline-flex items-center gap-2 text-gray-700 hover:text-amber-600">
            <i class="bi bi-bag text-xl"></i>
            <span
                v-if="block.settings?.show_count !== false"
                class="absolute -top-1 -right-1 bg-amber-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"
            >
                3
            </span>
            <span v-if="block.settings?.show_total" class="text-sm font-medium">Rs. 15,000</span>
        </a>
    </div>
</template>
