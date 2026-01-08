<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: 'desktop'
    }
})

const emit = defineEmits(['update:modelValue', 'change'])

const devices = [
    { id: 'desktop', icon: 'bi-display', label: 'Desktop', width: '100%' },
    { id: 'tablet', icon: 'bi-tablet', label: 'Tablet', width: '768px' },
    { id: 'mobile', icon: 'bi-phone', label: 'Mobile', width: '375px' },
]

const activeDevice = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value)
        emit('change', value)
    }
})

const currentDevice = computed(() => {
    return devices.find(d => d.id === activeDevice.value) || devices[0]
})

const selectDevice = (deviceId) => {
    activeDevice.value = deviceId
}
</script>

<template>
    <div class="responsive-toggle flex items-center gap-1 bg-gray-100 rounded-lg p-1">
        <button
            v-for="device in devices"
            :key="device.id"
            @click="selectDevice(device.id)"
            :class="[
                'flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm transition-all',
                activeDevice === device.id
                    ? 'bg-white shadow-sm text-gray-800'
                    : 'text-gray-500 hover:text-gray-700'
            ]"
            :title="device.label"
        >
            <i :class="[device.icon]"></i>
            <span class="hidden sm:inline">{{ device.label }}</span>
        </button>
    </div>
</template>

<style scoped>
.responsive-toggle button {
    min-width: 44px;
}
</style>
