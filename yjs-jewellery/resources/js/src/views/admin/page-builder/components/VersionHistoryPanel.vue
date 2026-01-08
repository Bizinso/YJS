<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/plugins/axiosAdmin'

const props = defineProps({
    pageId: {
        type: [Number, String],
        required: true
    },
    publishedVersionId: {
        type: [Number, String],
        default: null
    },
    draftVersionId: {
        type: [Number, String],
        default: null
    },
    scheduledVersionId: {
        type: [Number, String],
        default: null
    },
    scheduledAt: {
        type: String,
        default: null
    }
})

const emit = defineEmits(['revert', 'compare', 'publish', 'close'])

const loading = ref(true)
const reverting = ref(false)
const versions = ref([])
const selectedVersion = ref(null)
const compareMode = ref(false)
const compareVersions = ref([])

// Computed
const canRevert = computed(() => {
    return selectedVersion.value &&
           selectedVersion.value.id !== props.draftVersionId &&
           !reverting.value
})

const canCompare = computed(() => {
    return compareVersions.value.length === 2
})

// Methods
const fetchVersions = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(`/cms/pages/${props.pageId}/versions`)
        versions.value = data.data || []
    } catch (error) {
        console.error('Failed to fetch versions:', error)
    } finally {
        loading.value = false
    }
}

const formatDate = (dateString) => {
    if (!dateString) return ''
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
    if (!dateString) return ''
    const date = new Date(dateString)
    const now = new Date()
    const seconds = Math.floor((now - date) / 1000)

    if (seconds < 60) return 'Just now'
    if (seconds < 3600) return `${Math.floor(seconds / 60)} min ago`
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`
    if (seconds < 604800) return `${Math.floor(seconds / 86400)} days ago`
    return formatDate(dateString)
}

const getVersionBadges = (version) => {
    const badges = []

    if (version.id === props.publishedVersionId) {
        badges.push({ label: 'Published', color: 'green' })
    }
    if (version.id === props.draftVersionId) {
        badges.push({ label: 'Draft', color: 'blue' })
    }
    if (version.id === props.scheduledVersionId) {
        badges.push({ label: 'Scheduled', color: 'purple' })
    }
    if (version.status === 'archived') {
        badges.push({ label: 'Archived', color: 'gray' })
    }

    return badges
}

const selectVersion = (version) => {
    if (compareMode.value) {
        toggleCompareVersion(version)
    } else {
        selectedVersion.value = selectedVersion.value?.id === version.id ? null : version
    }
}

const toggleCompareVersion = (version) => {
    const index = compareVersions.value.findIndex(v => v.id === version.id)
    if (index > -1) {
        compareVersions.value.splice(index, 1)
    } else if (compareVersions.value.length < 2) {
        compareVersions.value.push(version)
    }
}

const isSelectedForCompare = (version) => {
    return compareVersions.value.some(v => v.id === version.id)
}

const revertToVersion = async () => {
    if (!canRevert.value) return

    const reason = prompt('Reason for reverting (optional):')

    reverting.value = true
    try {
        const { data } = await axios.post(
            `/cms/pages/${props.pageId}/versions/${selectedVersion.value.id}/revert`,
            { reason }
        )
        emit('revert', data.data)
        await fetchVersions()
        selectedVersion.value = null
    } catch (error) {
        console.error('Failed to revert version:', error)
        alert('Failed to revert version. Please try again.')
    } finally {
        reverting.value = false
    }
}

const publishVersion = async (version) => {
    if (!confirm(`Publish version ${version.version_number}?`)) return

    try {
        const { data } = await axios.post(`/cms/versions/${version.id}/publish`)
        emit('publish', data.data)
        await fetchVersions()
    } catch (error) {
        console.error('Failed to publish version:', error)
        alert('Failed to publish version. Please try again.')
    }
}

const doCompare = async () => {
    if (!canCompare.value) return

    try {
        const { data } = await axios.get('/cms/versions/compare', {
            params: {
                v1: compareVersions.value[0].id,
                v2: compareVersions.value[1].id
            }
        })
        emit('compare', data.data)
    } catch (error) {
        console.error('Failed to compare versions:', error)
    }
}

const toggleCompareMode = () => {
    compareMode.value = !compareMode.value
    if (!compareMode.value) {
        compareVersions.value = []
    }
}

const previewVersion = (version) => {
    window.open(`/cms/preview/${props.pageId}?version=${version.id}`, '_blank')
}

// Lifecycle
onMounted(() => {
    fetchVersions()
})

// Watch for page changes
watch(() => props.pageId, () => {
    fetchVersions()
})
</script>

<template>
    <div class="version-history-panel bg-white border-l border-gray-200 h-full flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-gray-800">Version History</h3>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Compare Mode Toggle -->
            <button
                @click="toggleCompareMode"
                :class="[
                    'w-full px-3 py-1.5 text-sm rounded border transition-colors',
                    compareMode
                        ? 'bg-purple-50 border-purple-300 text-purple-700'
                        : 'border-gray-300 text-gray-600 hover:bg-gray-50'
                ]"
            >
                <i class="bi bi-arrow-left-right mr-1"></i>
                {{ compareMode ? 'Exit Compare Mode' : 'Compare Versions' }}
            </button>

            <!-- Compare Action -->
            <button
                v-if="compareMode && canCompare"
                @click="doCompare"
                class="w-full mt-2 px-3 py-1.5 text-sm bg-purple-600 text-white rounded hover:bg-purple-700"
            >
                Compare Selected ({{ compareVersions.length }}/2)
            </button>
        </div>

        <!-- Scheduled Info -->
        <div v-if="scheduledVersionId && scheduledAt" class="px-4 py-2 bg-purple-50 border-b border-purple-100">
            <p class="text-sm text-purple-700">
                <i class="bi bi-clock mr-1"></i>
                Scheduled: {{ formatDate(scheduledAt) }}
            </p>
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
                        compareMode && isSelectedForCompare(version)
                            ? 'bg-purple-50 border-l-2 border-purple-500'
                            : selectedVersion?.id === version.id
                                ? 'bg-amber-50 border-l-2 border-amber-500'
                                : 'hover:bg-gray-50'
                    ]"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-gray-800">
                                    v{{ version.version_number }}
                                </span>
                                <span
                                    v-for="badge in getVersionBadges(version)"
                                    :key="badge.label"
                                    :class="[
                                        'px-2 py-0.5 text-xs rounded-full',
                                        badge.color === 'green' && 'bg-green-100 text-green-700',
                                        badge.color === 'blue' && 'bg-blue-100 text-blue-700',
                                        badge.color === 'purple' && 'bg-purple-100 text-purple-700',
                                        badge.color === 'gray' && 'bg-gray-100 text-gray-600'
                                    ]"
                                >
                                    {{ badge.label }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ version.title }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ getTimeAgo(version.created_at) }}
                            </p>
                            <p v-if="version.change_note" class="text-sm text-gray-600 mt-1 italic">
                                <i class="bi bi-chat-text text-xs mr-1"></i>
                                {{ version.change_note }}
                            </p>
                        </div>

                        <!-- Compare checkbox -->
                        <div v-if="compareMode" class="ml-2">
                            <input
                                type="checkbox"
                                :checked="isSelectedForCompare(version)"
                                :disabled="!isSelectedForCompare(version) && compareVersions.length >= 2"
                                class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                @click.stop
                            />
                        </div>

                        <!-- Created by -->
                        <div v-else class="text-right text-xs text-gray-400 ml-2">
                            <span v-if="version.created_by">
                                {{ version.created_by }}
                            </span>
                        </div>
                    </div>

                    <!-- Expanded Details (non-compare mode) -->
                    <div
                        v-if="!compareMode && selectedVersion?.id === version.id"
                        class="mt-3 pt-3 border-t border-gray-200"
                    >
                        <div class="flex gap-2 flex-wrap">
                            <button
                                @click.stop="previewVersion(version)"
                                class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50"
                            >
                                <i class="bi bi-eye mr-1"></i> Preview
                            </button>

                            <button
                                v-if="version.id !== publishedVersionId"
                                @click.stop="publishVersion(version)"
                                class="flex-1 px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                            >
                                <i class="bi bi-globe mr-1"></i> Publish
                            </button>

                            <button
                                v-if="version.id !== draftVersionId"
                                @click.stop="revertToVersion"
                                :disabled="reverting"
                                class="flex-1 px-3 py-1.5 text-sm bg-amber-600 text-white rounded hover:bg-amber-700 disabled:opacity-50"
                            >
                                <i class="bi bi-arrow-counterclockwise mr-1"></i>
                                {{ reverting ? 'Reverting...' : 'Revert' }}
                            </button>
                        </div>

                        <p class="text-xs text-gray-400 mt-2">
                            <i class="bi bi-calendar mr-1"></i>
                            {{ formatDate(version.created_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <p class="text-xs text-gray-500 text-center">
                <i class="bi bi-shield-check mr-1"></i>
                Versions are immutable - editing creates new versions
            </p>
        </div>
    </div>
</template>

<style scoped>
.version-history-panel {
    width: 320px;
    min-width: 320px;
}
</style>
