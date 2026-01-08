<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<template>
    <div class="p-4">
        <div class="relative group/user">
            <button class="flex items-center gap-2 text-gray-700 hover:text-amber-600">
                <div v-if="block.settings?.show_avatar !== false" class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                    <i class="bi bi-person text-gray-500"></i>
                </div>
                <span v-if="block.settings?.show_name !== false" class="text-sm font-medium">Account</span>
                <i class="bi bi-chevron-down text-xs"></i>
            </button>

            <!-- Dropdown preview -->
            <div class="hidden group-hover/user:block absolute top-full right-0 mt-2 bg-white border rounded-lg shadow-lg py-2 min-w-40 z-10">
                <a
                    v-for="(item, i) in (block.data?.guest_items || [])"
                    :key="i"
                    href="#"
                    class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-amber-600"
                >
                    {{ item.label }}
                </a>
            </div>
        </div>
    </div>
</template>
