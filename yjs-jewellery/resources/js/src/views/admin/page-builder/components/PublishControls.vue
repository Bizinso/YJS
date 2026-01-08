<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    isDirty: {
        type: Boolean,
        default: false
    },
    isSaving: {
        type: Boolean,
        default: false
    },
    isPublishing: {
        type: Boolean,
        default: false
    },
    publishedVersionNumber: {
        type: Number,
        default: null
    },
    draftVersionNumber: {
        type: Number,
        default: null
    },
    scheduledAt: {
        type: String,
        default: null
    }
})

const emit = defineEmits(['save', 'publish', 'schedule', 'unpublish', 'toggle-versions'])

const showScheduleModal = ref(false)
const scheduleDate = ref('')
const scheduleTime = ref('')

// Computed
const isPublished = computed(() => {
    return props.publishedVersionNumber !== null
})

const hasNewDraft = computed(() => {
    return props.draftVersionNumber !== null &&
           props.draftVersionNumber !== props.publishedVersionNumber
})

const isScheduled = computed(() => {
    return props.scheduledAt !== null
})

const statusLabel = computed(() => {
    if (isScheduled.value) return 'Scheduled'
    if (isPublished.value && !hasNewDraft.value) return 'Published'
    if (isPublished.value && hasNewDraft.value) return 'Draft Changes'
    return 'Draft'
})

const statusColor = computed(() => {
    if (isScheduled.value) return 'purple'
    if (isPublished.value && !hasNewDraft.value) return 'green'
    if (isPublished.value && hasNewDraft.value) return 'amber'
    return 'gray'
})

// Methods
const save = (note = null) => {
    const changeNote = note || (props.isDirty ? prompt('Describe your changes (optional):') : null)
    emit('save', changeNote)
}

const publish = () => {
    if (props.isDirty) {
        if (!confirm('You have unsaved changes. Save and publish?')) return
        emit('save')
    }
    emit('publish')
}

const openScheduleModal = () => {
    // Set default date/time to tomorrow at 9 AM
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    tomorrow.setHours(9, 0, 0, 0)

    scheduleDate.value = tomorrow.toISOString().split('T')[0]
    scheduleTime.value = '09:00'
    showScheduleModal.value = true
}

const confirmSchedule = () => {
    if (!scheduleDate.value || !scheduleTime.value) return

    const scheduledDateTime = new Date(`${scheduleDate.value}T${scheduleTime.value}`)
    emit('schedule', scheduledDateTime.toISOString())
    showScheduleModal.value = false
}

const unpublish = () => {
    if (!confirm('Unpublish this page? It will no longer be accessible to visitors.')) return
    emit('unpublish')
}

const formatScheduledDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <div class="publish-controls flex items-center gap-3">
        <!-- Status Badge -->
        <div
            :class="[
                'status-badge px-3 py-1 rounded-full text-xs font-medium',
                statusColor === 'green' && 'bg-green-100 text-green-700',
                statusColor === 'amber' && 'bg-amber-100 text-amber-700',
                statusColor === 'purple' && 'bg-purple-100 text-purple-700',
                statusColor === 'gray' && 'bg-gray-100 text-gray-600'
            ]"
        >
            <i
                :class="[
                    'mr-1',
                    isScheduled && 'bi-clock',
                    !isScheduled && isPublished && 'bi-globe',
                    !isScheduled && !isPublished && 'bi-file-earmark'
                ]"
            ></i>
            {{ statusLabel }}
            <template v-if="isScheduled">
                ({{ formatScheduledDate(scheduledAt) }})
            </template>
        </div>

        <!-- Version History Button -->
        <button
            @click="emit('toggle-versions')"
            class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg"
            title="Version History"
        >
            <i class="bi bi-clock-history"></i>
            <span class="ml-1 hidden sm:inline">v{{ draftVersionNumber || 1 }}</span>
        </button>

        <!-- Dirty Indicator -->
        <span
            v-if="isDirty"
            class="text-xs text-amber-600"
            title="Unsaved changes"
        >
            <i class="bi bi-circle-fill"></i>
        </span>

        <!-- Save Button -->
        <button
            @click="save()"
            :disabled="isSaving || !isDirty"
            class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <i :class="[isSaving ? 'bi-hourglass-split animate-spin' : 'bi-floppy', 'mr-1']"></i>
            {{ isSaving ? 'Saving...' : 'Save' }}
        </button>

        <!-- Schedule Button (dropdown) -->
        <div class="relative">
            <button
                @click="openScheduleModal"
                :disabled="isSaving || isPublishing"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                title="Schedule publish"
            >
                <i class="bi bi-calendar-event"></i>
            </button>
        </div>

        <!-- Publish Button -->
        <button
            @click="publish"
            :disabled="isSaving || isPublishing"
            class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
        >
            <i :class="[isPublishing ? 'bi-hourglass-split animate-spin' : 'bi-globe', 'mr-1']"></i>
            {{ isPublishing ? 'Publishing...' : 'Publish' }}
        </button>

        <!-- Unpublish (if published) -->
        <button
            v-if="isPublished"
            @click="unpublish"
            :disabled="isSaving || isPublishing"
            class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg"
            title="Unpublish"
        >
            <i class="bi bi-eye-slash"></i>
        </button>

        <!-- Schedule Modal -->
        <Teleport to="body">
            <div
                v-if="showScheduleModal"
                class="fixed inset-0 z-50 flex items-center justify-center"
            >
                <div
                    class="absolute inset-0 bg-black bg-opacity-50"
                    @click="showScheduleModal = false"
                ></div>
                <div class="relative bg-white rounded-xl shadow-xl p-6 w-96 z-10">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="bi bi-calendar-event mr-2"></i>
                        Schedule Publish
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Date
                            </label>
                            <input
                                v-model="scheduleDate"
                                type="date"
                                :min="new Date().toISOString().split('T')[0]"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Time
                            </label>
                            <input
                                v-model="scheduleTime"
                                type="time"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button
                            @click="showScheduleModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmSchedule"
                            :disabled="!scheduleDate || !scheduleTime"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
                        >
                            <i class="bi bi-clock mr-1"></i>
                            Schedule
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.publish-controls {
    flex-wrap: wrap;
}

@media (max-width: 640px) {
    .publish-controls {
        gap: 0.5rem;
    }
}
</style>
