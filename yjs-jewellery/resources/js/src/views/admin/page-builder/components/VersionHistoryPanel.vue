<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/plugins/axiosAdmin'

const props = defineProps({
    pageId: {
        type: [Number, String],
        required: true
    }
})

const emit = defineEmits(['restore', 'close'])

const loading = ref(true)
const restoring = ref(false)
const versions = ref([])
const selectedVersion = ref(null)

const fetchVersions = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/admin/cms/pages/${props.pageId}/versions`)
        versions.value = data.data || []
    } catch (error) {
        console.error('Failed to fetch versions:', error)
    } finally {
        loading.value = false
    }
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getTimeAgo = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const seconds = Math.floor((now - date) / 1000)

    if (seconds < 60) return 'Just now'
    if (seconds < 3600) return `${Math.floor(seconds / 60)} min ago`
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`
    if (seconds < 604800) return `${Math.floor(seconds / 86400)} days ago`
    return formatDate(dateString)
}

const selectVersion = (version) => {
    selectedVersion.value = selectedVersion.value?.id === version.id ? null : version
}

const restoreVersion = async () => {
    if (!selectedVersion.value || restoring.value) return

    if (!confirm(`Restore to version ${selectedVersion.value.version}? Your current changes will be saved as a new version first.`)) {
        return
    }

    restoring.value = true
    try {
        const { data } = await axios.post(`/admin/cms/pages/${props.pageId}/versions/${selectedVersion.value.id}/restore`)
        emit('restore', data.data)
    } catch (error) {
        console.error('Failed to restore version:', error)
        alert('Failed to restore version. Please try again.')
    } finally {
        restoring.value = false
    }
}

const previewVersion = (version) => {
    // Open preview in new tab
    window.open(`/admin/cms/pages/${props.pageId}/preview?version=${version.id}`, '_blank')
}

onMounted(() => {
    fetchVersions()
})
</script>

<template>
    <div class="version-history-panel bg-white border-l border-gray-200 h-full flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Version History</h3>
            <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Loading -->
            <div v-if="loading" class="p-4 text-center">
                <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="text-sm text-gray-500 mt-2">Loading versions...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="versions.length === 0" class="p-4 text-center">
                <i class="bi bi-clock-history text-3xl text-gray-300"></i>
                <p class="text-sm text-gray-500 mt-2">No version history yet</p>
            </div>

            <!-- Version List -->
            <div v-else class="divide-y divide-gray-100">
                <div
                    v-for="version in versions"
                    :key="version.id"
                    @click="selectVersion(version)"
                    :class="[
                        'p-4 cursor-pointer transition-colors',
                        selectedVersion?.id === version.id
                            ? 'bg-amber-50 border-l-2 border-amber-500'
                            : 'hover:bg-gray-50'
                    ]"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-800">
                                    Version {{ version.version }}
                                </span>
                                <span
                                    v-if="version.is_current"
                                    class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full"
                                >
                                    Current
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ getTimeAgo(version.created_at) }}
                            </p>
                            <p v-if="version.change_note" class="text-sm text-gray-600 mt-1">
                                <i class="bi bi-chat-text text-xs mr-1"></i>
                                {{ version.change_note }}
                            </p>
                        </div>
                        <div class="text-right text-xs text-gray-400">
                            <span v-if="version.created_by_name">
                                {{ version.created_by_name }}
                            </span>
                        </div>
                    </div>

                    <!-- Expanded Details -->
                    <div v-if="selectedVersion?.id === version.id" class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex gap-2">
                            <button
                                @click.stop="previewVersion(version)"
                                class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50"
                            >
                                <i class="bi bi-eye mr-1"></i> Preview
                            </button>
                            <button
                                v-if="!version.is_current"
                                @click.stop="restoreVersion"
                                :disabled="restoring"
                                class="flex-1 px-3 py-1.5 text-sm bg-amber-600 text-white rounded hover:bg-amber-700 disabled:opacity-50"
                            >
                                <i class="bi bi-arrow-counterclockwise mr-1"></i>
                                {{ restoring ? 'Restoring...' : 'Restore' }}
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            <i class="bi bi-info-circle mr-1"></i>
                            {{ formatDate(version.created_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <p class="text-xs text-gray-500 text-center">
                <i class="bi bi-info-circle mr-1"></i>
                Versions are automatically created when you save
            </p>
        </div>
    </div>
</template>

<style scoped>
.version-history-panel {
    width: 320px;
}
</style>
