import { ref, computed, readonly } from 'vue'
import axiosAdmin from '@/plugins/axiosAdmin'

/**
 * useVersioning - Core version management for CMS Kernel
 *
 * Key Principles:
 * - Versions are immutable (never modified after creation)
 * - Editing creates NEW versions
 * - Publishing switches pointers, not content
 * - Rollback creates NEW version from history
 */
export function useVersioning(pageId = null) {
    // State
    const currentVersion = ref(null)
    const publishedVersion = ref(null)
    const draftVersion = ref(null)
    const scheduledVersion = ref(null)
    const scheduledAt = ref(null)
    const versionHistory = ref([])
    const isLoading = ref(false)
    const isSaving = ref(false)
    const isPublishing = ref(false)
    const error = ref(null)

    // Track if there are unsaved changes since last save
    const hasUnsavedChanges = ref(false)

    // Computed
    const isDraft = computed(() => {
        return currentVersion.value?.status === 'draft'
    })

    const isPublished = computed(() => {
        return publishedVersion.value?.id === currentVersion.value?.id
    })

    const isScheduled = computed(() => {
        return scheduledVersion.value?.id === currentVersion.value?.id
    })

    const canPublish = computed(() => {
        return currentVersion.value && !isPublishing.value
    })

    const canRevert = computed(() => {
        return versionHistory.value.length > 1
    })

    const publishedVersionNumber = computed(() => {
        return publishedVersion.value?.version_number || null
    })

    const draftVersionNumber = computed(() => {
        return draftVersion.value?.version_number || null
    })

    // Methods

    /**
     * Load a page with its version data
     */
    const loadPage = async (id) => {
        isLoading.value = true
        error.value = null

        try {
            const response = await axiosAdmin.get(`/cms/pages/${id}`)

            if (response.data.success) {
                const data = response.data.data

                // Set version pointers
                publishedVersion.value = data.published_version || null
                draftVersion.value = data.draft_version || null
                scheduledVersion.value = data.scheduled_version || null
                scheduledAt.value = data.scheduled_at || null

                // Current version is the draft for editing
                currentVersion.value = draftVersion.value
                hasUnsavedChanges.value = false

                return data
            } else {
                throw new Error(response.data.message || 'Failed to load page')
            }
        } catch (err) {
            error.value = err.message || 'Failed to load page'
            console.error('Error loading page:', err)
            throw err
        } finally {
            isLoading.value = false
        }
    }

    /**
     * Load version history for a page
     */
    const loadHistory = async (id = null) => {
        const targetId = id || pageId
        if (!targetId) return

        try {
            const response = await axiosAdmin.get(`/cms/pages/${targetId}/versions`)

            if (response.data.success) {
                versionHistory.value = response.data.data || []
            }
        } catch (err) {
            console.error('Error loading version history:', err)
        }
    }

    /**
     * Save changes - creates a NEW version (immutable)
     */
    const saveVersion = async (pageIdParam, changes, note = null) => {
        const targetId = pageIdParam || pageId
        if (!targetId || isSaving.value) return null

        isSaving.value = true
        error.value = null

        try {
            const response = await axiosAdmin.put(`/cms/pages/${targetId}`, {
                ...changes,
                change_note: note
            })

            if (response.data.success) {
                const data = response.data.data

                // Update version pointers
                draftVersion.value = data.draft_version || null
                currentVersion.value = draftVersion.value
                hasUnsavedChanges.value = false

                // Refresh history
                await loadHistory(targetId)

                return data
            } else {
                throw new Error(response.data.message || 'Failed to save')
            }
        } catch (err) {
            error.value = err.message || 'Failed to save version'
            console.error('Error saving version:', err)
            throw err
        } finally {
            isSaving.value = false
        }
    }

    /**
     * Publish a version - switches pointer, not content
     */
    const publish = async (versionId = null) => {
        const targetVersionId = versionId || draftVersion.value?.id
        if (!targetVersionId || isPublishing.value) return null

        isPublishing.value = true
        error.value = null

        try {
            const response = await axiosAdmin.post(`/cms/versions/${targetVersionId}/publish`)

            if (response.data.success) {
                const data = response.data.data

                // Update published version pointer
                publishedVersion.value = data

                // Update current version status
                if (currentVersion.value?.id === targetVersionId) {
                    currentVersion.value = { ...currentVersion.value, status: 'published' }
                }

                // Refresh history
                await loadHistory()

                return data
            } else {
                throw new Error(response.data.message || 'Failed to publish')
            }
        } catch (err) {
            error.value = err.message || 'Failed to publish'
            console.error('Error publishing:', err)
            throw err
        } finally {
            isPublishing.value = false
        }
    }

    /**
     * Unpublish a page - clears published pointer
     */
    const unpublish = async (pageIdParam = null) => {
        const targetId = pageIdParam || pageId
        if (!targetId) return

        try {
            const response = await axiosAdmin.post(`/cms/pages/${targetId}/unpublish`)

            if (response.data.success) {
                publishedVersion.value = null
                await loadHistory(targetId)
            }
        } catch (err) {
            error.value = err.message || 'Failed to unpublish'
            throw err
        }
    }

    /**
     * Revert to a historical version - creates NEW version
     */
    const revertTo = async (pageIdParam, targetVersionId, reason = null) => {
        const targetPageId = pageIdParam || pageId
        if (!targetPageId || !targetVersionId) return null

        isSaving.value = true
        error.value = null

        try {
            const response = await axiosAdmin.post(
                `/cms/pages/${targetPageId}/versions/${targetVersionId}/revert`,
                { reason }
            )

            if (response.data.success) {
                const data = response.data.data

                // New version created from revert
                draftVersion.value = data
                currentVersion.value = data
                hasUnsavedChanges.value = false

                // Refresh history
                await loadHistory(targetPageId)

                return data
            } else {
                throw new Error(response.data.message || 'Failed to revert')
            }
        } catch (err) {
            error.value = err.message || 'Failed to revert'
            console.error('Error reverting:', err)
            throw err
        } finally {
            isSaving.value = false
        }
    }

    /**
     * Schedule a version for future publish
     */
    const schedule = async (versionId, publishAt) => {
        if (!versionId || !publishAt) return null

        try {
            const response = await axiosAdmin.post(`/cms/versions/${versionId}/schedule`, {
                publish_at: publishAt
            })

            if (response.data.success) {
                scheduledVersion.value = response.data.data
                scheduledAt.value = publishAt
                return response.data.data
            }
        } catch (err) {
            error.value = err.message || 'Failed to schedule'
            throw err
        }
    }

    /**
     * Cancel scheduled publish
     */
    const unschedule = async (pageIdParam = null) => {
        const targetId = pageIdParam || pageId
        if (!targetId) return

        try {
            const response = await axiosAdmin.delete(`/cms/pages/${targetId}/schedule`)

            if (response.data.success) {
                scheduledVersion.value = null
                scheduledAt.value = null
            }
        } catch (err) {
            error.value = err.message || 'Failed to unschedule'
            throw err
        }
    }

    /**
     * Compare two versions
     */
    const compare = async (v1Id, v2Id) => {
        try {
            const response = await axiosAdmin.get('/cms/versions/compare', {
                params: { v1: v1Id, v2: v2Id }
            })

            if (response.data.success) {
                return response.data.data
            }
        } catch (err) {
            console.error('Error comparing versions:', err)
            throw err
        }
    }

    /**
     * Load a specific version (for viewing/comparison)
     */
    const loadVersion = async (versionId) => {
        try {
            const response = await axiosAdmin.get(`/cms/versions/${versionId}`)

            if (response.data.success) {
                return response.data.data
            }
        } catch (err) {
            console.error('Error loading version:', err)
            throw err
        }
    }

    /**
     * Switch to editing a different version
     */
    const switchToVersion = (version) => {
        currentVersion.value = version
        hasUnsavedChanges.value = false
    }

    /**
     * Mark that changes have been made
     */
    const markDirty = () => {
        hasUnsavedChanges.value = true
    }

    /**
     * Reset state
     */
    const reset = () => {
        currentVersion.value = null
        publishedVersion.value = null
        draftVersion.value = null
        scheduledVersion.value = null
        scheduledAt.value = null
        versionHistory.value = []
        hasUnsavedChanges.value = false
        error.value = null
    }

    return {
        // State (readonly where appropriate)
        currentVersion: readonly(currentVersion),
        publishedVersion: readonly(publishedVersion),
        draftVersion: readonly(draftVersion),
        scheduledVersion: readonly(scheduledVersion),
        scheduledAt: readonly(scheduledAt),
        versionHistory: readonly(versionHistory),
        isLoading: readonly(isLoading),
        isSaving: readonly(isSaving),
        isPublishing: readonly(isPublishing),
        error: readonly(error),
        hasUnsavedChanges: readonly(hasUnsavedChanges),

        // Computed
        isDraft,
        isPublished,
        isScheduled,
        canPublish,
        canRevert,
        publishedVersionNumber,
        draftVersionNumber,

        // Methods
        loadPage,
        loadHistory,
        saveVersion,
        publish,
        unpublish,
        revertTo,
        schedule,
        unschedule,
        compare,
        loadVersion,
        switchToVersion,
        markDirty,
        reset
    }
}
