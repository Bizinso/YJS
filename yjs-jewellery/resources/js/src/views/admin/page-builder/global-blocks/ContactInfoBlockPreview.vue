<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <div class="p-4">
        <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-600">
            <div v-if="block.data?.phone" class="flex items-center gap-2">
                <i v-if="block.settings?.show_icons !== false" class="bi bi-telephone text-amber-600"></i>
                <span>{{ block.data.phone }}</span>
            </div>
            <div v-if="block.data?.email" class="flex items-center gap-2">
                <i v-if="block.settings?.show_icons !== false" class="bi bi-envelope text-amber-600"></i>
                <span>{{ block.data.email }}</span>
            </div>
            <div v-if="block.data?.address" class="flex items-center gap-2">
                <i v-if="block.settings?.show_icons !== false" class="bi bi-geo-alt text-amber-600"></i>
                <span>{{ block.data.address }}</span>
            </div>
        </div>
    </div>
</template>
