<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import draggable from 'vuedraggable'
import axiosAdmin from '@/plugins/axiosAdmin'
import { v4 as uuidv4 } from 'uuid'

// Header block components
import LogoBlockPreview from './global-blocks/LogoBlockPreview.vue'
import NavigationBlockPreview from './global-blocks/NavigationBlockPreview.vue'
import SearchBlockPreview from './global-blocks/SearchBlockPreview.vue'
import CartIconBlockPreview from './global-blocks/CartIconBlockPreview.vue'
import UserMenuBlockPreview from './global-blocks/UserMenuBlockPreview.vue'
import AnnouncementBarBlockPreview from './global-blocks/AnnouncementBarBlockPreview.vue'
// Footer block components
import FooterLinksBlockPreview from './global-blocks/FooterLinksBlockPreview.vue'
import SocialIconsBlockPreview from './global-blocks/SocialIconsBlockPreview.vue'
import NewsletterBlockPreview from './global-blocks/NewsletterBlockPreview.vue'
import CopyrightBlockPreview from './global-blocks/CopyrightBlockPreview.vue'
import ContactInfoBlockPreview from './global-blocks/ContactInfoBlockPreview.vue'

// Header editors
import LogoEditor from './global-editors/LogoEditor.vue'
import NavigationEditor from './global-editors/NavigationEditor.vue'
import SearchEditor from './global-editors/SearchEditor.vue'
import CartIconEditor from './global-editors/CartIconEditor.vue'
import UserMenuEditor from './global-editors/UserMenuEditor.vue'
import AnnouncementBarEditor from './global-editors/AnnouncementBarEditor.vue'
// Footer editors
import FooterLinksEditor from './global-editors/FooterLinksEditor.vue'
import SocialIconsEditor from './global-editors/SocialIconsEditor.vue'
import NewsletterEditor from './global-editors/NewsletterEditor.vue'
import CopyrightEditor from './global-editors/CopyrightEditor.vue'
import ContactInfoEditor from './global-editors/ContactInfoEditor.vue'

const route = useRoute()
const router = useRouter()

const sectionType = computed(() => route.params.type || 'header')
const loading = ref(true)
const saving = ref(false)
const publishing = ref(false)
const section = ref(null)
const blocks = ref([])
const selectedBlockId = ref(null)
const isDirty = ref(false)

// Version state
const versionHistory = ref([])
const showVersionPanel = ref(false)
const showChangeNoteModal = ref(false)
const changeNote = ref('')
const loadingVersions = ref(false)
const selectedVersionId = ref(null)
const previewVersion = ref(null)

const previewComponents = {
    logo: LogoBlockPreview,
    navigation: NavigationBlockPreview,
    search: SearchBlockPreview,
    cart_icon: CartIconBlockPreview,
    user_menu: UserMenuBlockPreview,
    announcement_bar: AnnouncementBarBlockPreview,
    footer_links: FooterLinksBlockPreview,
    social_icons: SocialIconsBlockPreview,
    newsletter: NewsletterBlockPreview,
    copyright: CopyrightBlockPreview,
    contact_info: ContactInfoBlockPreview,
}

const editorComponents = {
    logo: LogoEditor,
    navigation: NavigationEditor,
    search: SearchEditor,
    cart_icon: CartIconEditor,
    user_menu: UserMenuEditor,
    announcement_bar: AnnouncementBarEditor,
    footer_links: FooterLinksEditor,
    social_icons: SocialIconsEditor,
    newsletter: NewsletterEditor,
    copyright: CopyrightEditor,
    contact_info: ContactInfoEditor,
}

const headerBlocks = [
    { type: 'announcement_bar', label: 'Announcement Bar', icon: 'bi-megaphone' },
    { type: 'logo', label: 'Logo', icon: 'bi-image' },
    { type: 'navigation', label: 'Navigation Menu', icon: 'bi-list' },
    { type: 'search', label: 'Search Bar', icon: 'bi-search' },
    { type: 'cart_icon', label: 'Cart Icon', icon: 'bi-cart' },
    { type: 'user_menu', label: 'User Menu', icon: 'bi-person' },
]

const footerBlocks = [
    { type: 'logo', label: 'Logo', icon: 'bi-image' },
    { type: 'footer_links', label: 'Link Columns', icon: 'bi-link-45deg' },
    { type: 'social_icons', label: 'Social Icons', icon: 'bi-share' },
    { type: 'newsletter', label: 'Newsletter', icon: 'bi-envelope' },
    { type: 'contact_info', label: 'Contact Info', icon: 'bi-telephone' },
    { type: 'copyright', label: 'Copyright', icon: 'bi-c-circle' },
]

const availableBlocks = computed(() => sectionType.value === 'header' ? headerBlocks : footerBlocks)

const selectedBlock = computed(() => {
    if (!selectedBlockId.value) return null
    return blocks.value.find(b => b.id === selectedBlockId.value)
})

// Computed for version status
const hasUnpublishedChanges = computed(() => section.value?.has_unpublished_changes ?? false)
const isPublished = computed(() => section.value?.is_published ?? false)
const draftVersionNumber = computed(() => section.value?.draft_version_number ?? 0)
const publishedVersionNumber = computed(() => section.value?.published_version_number ?? null)

const blockDefaults = {
    logo: {
        data: { image_url: '', alt: 'Logo', link: '/' },
        settings: { max_width: 150, mobile_width: 100 }
    },
    navigation: {
        data: { items: [{ label: 'Home', url: '/', children: [] }] },
        settings: { style: 'horizontal', show_icons: false, mobile_breakpoint: 768 }
    },
    search: {
        data: { placeholder: 'Search products...' },
        settings: { style: 'inline', show_suggestions: true }
    },
    cart_icon: {
        data: {},
        settings: { show_count: true, show_total: false }
    },
    user_menu: {
        data: { guest_items: [{ label: 'Login', url: '/login' }, { label: 'Register', url: '/register' }], logged_in_items: [{ label: 'My Account', url: '/account' }, { label: 'Orders', url: '/orders' }, { label: 'Logout', url: '/logout' }] },
        settings: { show_name: true, show_avatar: true }
    },
    announcement_bar: {
        data: { messages: [{ text: 'Free shipping on orders over Rs. 5000!' }] },
        settings: { background: '#1a1a1a', text_color: '#ffffff', rotate: true, rotate_interval: 5000 }
    },
    footer_links: {
        data: { columns: [{ title: 'Quick Links', links: [{ label: 'About Us', url: '/about' }, { label: 'Contact', url: '/contact' }] }] },
        settings: { columns_count: 3 }
    },
    social_icons: {
        data: { icons: [{ platform: 'facebook', url: '#' }, { platform: 'instagram', url: '#' }, { platform: 'twitter', url: '#' }] },
        settings: { size: 'medium', style: 'filled' }
    },
    newsletter: {
        data: { title: 'Subscribe to our Newsletter', description: 'Get updates on new arrivals and offers', button_text: 'Subscribe' },
        settings: { style: 'inline' }
    },
    copyright: {
        data: { text: '© {year} YJS Jewellery. All rights reserved.', links: [{ label: 'Privacy Policy', url: '/privacy' }, { label: 'Terms', url: '/terms' }] },
        settings: {}
    },
    contact_info: {
        data: { phone: '+91 12345 67890', email: 'info@yjsjewellery.com', address: '123 Jewellery Lane, Mumbai' },
        settings: { show_icons: true }
    }
}

onMounted(async () => {
    await loadSection()
})

watch(() => route.params.type, () => {
    loadSection()
})

async function loadSection() {
    loading.value = true
    previewVersion.value = null
    selectedVersionId.value = null
    try {
        const { data } = await axiosAdmin.get('/cms/global-sections')
        const sectionData = data.data[sectionType.value]

        if (sectionData) {
            section.value = sectionData
            blocks.value = sectionData.blocks || sectionData.widgets || []
        } else {
            section.value = {
                type: sectionType.value,
                name: sectionType.value === 'header' ? 'Header' : 'Footer',
                is_active: true,
                is_published: false,
                has_unpublished_changes: false,
                draft_version_number: 0
            }
            blocks.value = []
        }
        selectedBlockId.value = null
        isDirty.value = false
    } catch (error) {
        console.error('Failed to load section:', error)
    } finally {
        loading.value = false
    }
}

async function loadVersionHistory() {
    if (!section.value?.id) return

    loadingVersions.value = true
    try {
        const { data } = await axiosAdmin.get(`/cms/global-sections/${section.value.id}/versions`)
        versionHistory.value = data.data
    } catch (error) {
        console.error('Failed to load versions:', error)
    } finally {
        loadingVersions.value = false
    }
}

async function loadVersion(versionId) {
    try {
        const { data } = await axiosAdmin.get(`/cms/global-section-versions/${versionId}`)
        previewVersion.value = data.data
        selectedVersionId.value = versionId
    } catch (error) {
        console.error('Failed to load version:', error)
    }
}

function addBlock(blockType) {
    const defaults = blockDefaults[blockType] || { data: {}, settings: {} }
    const newBlock = {
        id: uuidv4(),
        type: blockType,
        visible: true,
        data: { ...defaults.data },
        settings: { ...defaults.settings }
    }
    blocks.value.push(newBlock)
    selectedBlockId.value = newBlock.id
    isDirty.value = true
}

function selectBlock(id) {
    selectedBlockId.value = selectedBlockId.value === id ? null : id
}

function deleteBlock(id) {
    const index = blocks.value.findIndex(b => b.id === id)
    if (index > -1) {
        blocks.value.splice(index, 1)
        if (selectedBlockId.value === id) {
            selectedBlockId.value = null
        }
        isDirty.value = true
    }
}

function duplicateBlock(id) {
    const block = blocks.value.find(b => b.id === id)
    if (block) {
        const newBlock = JSON.parse(JSON.stringify(block))
        newBlock.id = uuidv4()
        const index = blocks.value.findIndex(b => b.id === id)
        blocks.value.splice(index + 1, 0, newBlock)
        selectedBlockId.value = newBlock.id
        isDirty.value = true
    }
}

function toggleVisibility(id) {
    const block = blocks.value.find(b => b.id === id)
    if (block) {
        block.visible = !block.visible
        isDirty.value = true
    }
}

function updateBlockData(updates) {
    if (selectedBlock.value) {
        selectedBlock.value.data = { ...selectedBlock.value.data, ...updates }
        isDirty.value = true
    }
}

function updateBlockSettings(updates) {
    if (selectedBlock.value) {
        selectedBlock.value.settings = { ...selectedBlock.value.settings, ...updates }
        isDirty.value = true
    }
}

function onDragEnd() {
    isDirty.value = true
}

// Open change note modal before saving
function promptSave() {
    if (!isDirty.value) return
    changeNote.value = ''
    showChangeNoteModal.value = true
}

// Save creates NEW version (immutable)
async function save() {
    saving.value = true
    showChangeNoteModal.value = false
    try {
        const endpoint = section.value?.id
            ? `/cms/global-sections/${section.value.id}`
            : `/cms/global-sections/type/${sectionType.value}`

        const { data } = await axiosAdmin.put(endpoint, {
            name: section.value.name,
            widgets: blocks.value,
            settings: section.value.settings || {},
            is_active: section.value.is_active,
            change_note: changeNote.value || null
        })

        section.value = data.data
        isDirty.value = false
        changeNote.value = ''

        // Refresh version history if panel is open
        if (showVersionPanel.value) {
            await loadVersionHistory()
        }
    } catch (error) {
        console.error('Failed to save:', error)
        alert('Failed to save. Please try again.')
    } finally {
        saving.value = false
    }
}

// Publish current draft
async function publish() {
    if (!section.value?.id) {
        alert('Please save the section first')
        return
    }

    publishing.value = true
    try {
        const { data } = await axiosAdmin.post(`/cms/global-sections/${section.value.id}/publish`)
        section.value = data.data

        // Refresh version history if panel is open
        if (showVersionPanel.value) {
            await loadVersionHistory()
        }
    } catch (error) {
        console.error('Failed to publish:', error)
        alert('Failed to publish. Please try again.')
    } finally {
        publishing.value = false
    }
}

// Unpublish section
async function unpublish() {
    if (!section.value?.id || !confirm('Are you sure you want to unpublish this section?')) return

    try {
        await axiosAdmin.post(`/cms/global-sections/${section.value.id}/unpublish`)
        section.value.is_published = false
        section.value.published_version_id = null
        section.value.published_version_number = null

        // Refresh version history if panel is open
        if (showVersionPanel.value) {
            await loadVersionHistory()
        }
    } catch (error) {
        console.error('Failed to unpublish:', error)
        alert('Failed to unpublish. Please try again.')
    }
}

// Rollback to previous version (creates NEW version)
async function rollbackToVersion(versionId) {
    const version = versionHistory.value.find(v => v.id === versionId)
    if (!version) return

    if (!confirm(`Rollback to version ${version.version_number}? This will create a new version based on that content.`)) {
        return
    }

    try {
        const { data } = await axiosAdmin.post(`/cms/global-sections/${section.value.id}/rollback`, {
            version_id: versionId,
            reason: `Rolled back to version ${version.version_number}`
        })

        section.value = data.data
        blocks.value = data.data.blocks || data.data.widgets || []
        previewVersion.value = null
        selectedVersionId.value = null
        await loadVersionHistory()
    } catch (error) {
        console.error('Failed to rollback:', error)
        alert('Failed to rollback. Please try again.')
    }
}

// Apply preview version to canvas (for comparison)
function applyPreviewVersion() {
    if (previewVersion.value) {
        blocks.value = previewVersion.value.widgets || []
        isDirty.value = true
        previewVersion.value = null
        selectedVersionId.value = null
        showVersionPanel.value = false
    }
}

// Cancel preview
function cancelPreview() {
    previewVersion.value = null
    selectedVersionId.value = null
    loadSection()
}

function toggleVersionPanel() {
    showVersionPanel.value = !showVersionPanel.value
    if (showVersionPanel.value && versionHistory.value.length === 0) {
        loadVersionHistory()
    }
}

function goBack() {
    if (isDirty.value && !confirm('You have unsaved changes. Are you sure you want to leave?')) {
        return
    }
    router.push('/admin/page-builder')
}

function getBlockLabel(type) {
    const allBlocks = [...headerBlocks, ...footerBlocks]
    return allBlocks.find(b => b.type === type)?.label || type
}

function formatDate(dateString) {
    if (!dateString) return ''
    return new Date(dateString).toLocaleString()
}
</script>

<template>
    <div class="global-section-builder h-screen flex flex-col bg-gray-100">
        <!-- Top Toolbar -->
        <div class="bg-white border-b px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button @click="goBack" class="text-gray-600 hover:text-gray-900">
                    <i class="bi bi-arrow-left text-xl"></i>
                </button>
                <div>
                    <h1 class="text-lg font-semibold flex items-center gap-2">
                        Edit {{ sectionType === 'header' ? 'Header' : 'Footer' }}
                        <span v-if="draftVersionNumber" class="text-sm font-normal text-gray-500">
                            v{{ draftVersionNumber }}
                        </span>
                    </h1>
                    <p class="text-xs text-gray-500">
                        Global section that appears on all pages
                        <template v-if="hasUnpublishedChanges">
                            <span class="text-amber-600 font-medium">• Unpublished changes</span>
                        </template>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Type Switcher -->
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <router-link
                        to="/admin/page-builder/global/header"
                        :class="['px-4 py-1.5 rounded text-sm font-medium transition-colors', sectionType === 'header' ? 'bg-white shadow text-amber-600' : 'text-gray-600 hover:text-gray-900']"
                    >
                        <i class="bi bi-layout-text-window-reverse me-1"></i> Header
                    </router-link>
                    <router-link
                        to="/admin/page-builder/global/footer"
                        :class="['px-4 py-1.5 rounded text-sm font-medium transition-colors', sectionType === 'footer' ? 'bg-white shadow text-amber-600' : 'text-gray-600 hover:text-gray-900']"
                    >
                        <i class="bi bi-layout-text-window me-1"></i> Footer
                    </router-link>
                </div>

                <!-- Version History Toggle -->
                <button
                    @click="toggleVersionPanel"
                    :class="['px-3 py-2 rounded-lg flex items-center gap-2 transition-colors', showVersionPanel ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']"
                    :disabled="!section?.id"
                >
                    <i class="bi bi-clock-history"></i>
                    <span class="text-sm">History</span>
                </button>

                <!-- Active Toggle -->
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="section.is_active" class="rounded border-gray-300 text-amber-600" @change="isDirty = true">
                    <span>Active</span>
                </label>

                <!-- Save Button -->
                <button
                    @click="promptSave"
                    :disabled="saving || !isDirty"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <i v-if="saving" class="bi bi-arrow-repeat animate-spin"></i>
                    <i v-else class="bi bi-save"></i>
                    {{ saving ? 'Saving...' : 'Save Draft' }}
                </button>

                <!-- Publish Button -->
                <button
                    @click="publish"
                    :disabled="publishing || !section?.id || (!isDirty && !hasUnpublishedChanges)"
                    class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <i v-if="publishing" class="bi bi-arrow-repeat animate-spin"></i>
                    <i v-else class="bi bi-globe"></i>
                    {{ publishing ? 'Publishing...' : 'Publish' }}
                </button>

                <!-- Unpublish -->
                <button
                    v-if="isPublished"
                    @click="unpublish"
                    class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg"
                    title="Unpublish"
                >
                    <i class="bi bi-globe2"></i>
                </button>
            </div>
        </div>

        <!-- Preview Version Banner -->
        <div v-if="previewVersion" class="bg-amber-50 border-b border-amber-200 px-4 py-2 flex items-center justify-between">
            <div class="flex items-center gap-2 text-amber-800">
                <i class="bi bi-eye"></i>
                <span>Previewing version {{ previewVersion.version_number }} from {{ formatDate(previewVersion.created_at) }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="applyPreviewVersion" class="px-3 py-1 bg-amber-600 text-white rounded text-sm hover:bg-amber-700">
                    Apply This Version
                </button>
                <button @click="cancelPreview" class="px-3 py-1 bg-white border border-amber-300 text-amber-700 rounded text-sm hover:bg-amber-50">
                    Cancel Preview
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Left: Available Blocks -->
            <div class="w-64 bg-white border-r overflow-y-auto p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    {{ sectionType === 'header' ? 'Header' : 'Footer' }} Blocks
                </h3>
                <div class="space-y-2">
                    <button
                        v-for="block in availableBlocks"
                        :key="block.type"
                        @click="addBlock(block.type)"
                        class="w-full flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-amber-50 hover:border-amber-200 border border-transparent transition-colors text-left"
                    >
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center border">
                            <i :class="[block.icon, 'text-lg text-gray-600']"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">{{ block.label }}</span>
                    </button>
                </div>
            </div>

            <!-- Center: Preview Canvas -->
            <div class="flex-1 overflow-auto p-6">
                <div v-if="loading" class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <i class="bi bi-arrow-repeat animate-spin text-3xl text-amber-600"></i>
                        <p class="mt-2 text-gray-500">Loading...</p>
                    </div>
                </div>

                <div v-else class="max-w-4xl mx-auto">
                    <!-- Preview Container -->
                    <div :class="['bg-white rounded-lg shadow-sm border overflow-hidden', sectionType === 'header' ? 'rounded-b-none' : 'rounded-t-none']">
                        <div v-if="blocks.length === 0" class="p-12 text-center text-gray-400">
                            <i :class="[sectionType === 'header' ? 'bi-layout-text-window-reverse' : 'bi-layout-text-window', 'text-4xl mb-3']"></i>
                            <p>Click a block on the left to add it to the {{ sectionType }}</p>
                        </div>

                        <draggable
                            v-else
                            v-model="blocks"
                            item-key="id"
                            handle=".drag-handle"
                            ghost-class="opacity-50"
                            @end="onDragEnd"
                            :class="sectionType === 'header' ? '' : 'divide-y'"
                        >
                            <template #item="{ element }">
                                <div
                                    :class="[
                                        'block-wrapper relative group transition-all',
                                        selectedBlockId === element.id ? 'ring-2 ring-amber-500 ring-inset' : 'hover:ring-2 hover:ring-gray-200 hover:ring-inset',
                                        !element.visible && 'opacity-40'
                                    ]"
                                    @click="selectBlock(element.id)"
                                >
                                    <!-- Block Controls -->
                                    <div class="absolute top-2 right-2 z-10 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button class="drag-handle p-1.5 bg-white rounded shadow hover:bg-gray-50 cursor-move" title="Drag to reorder">
                                            <i class="bi bi-grip-vertical text-gray-400"></i>
                                        </button>
                                        <button @click.stop="toggleVisibility(element.id)" class="p-1.5 bg-white rounded shadow hover:bg-gray-50" :title="element.visible ? 'Hide' : 'Show'">
                                            <i :class="element.visible ? 'bi-eye text-gray-400' : 'bi-eye-slash text-gray-400'"></i>
                                        </button>
                                        <button @click.stop="duplicateBlock(element.id)" class="p-1.5 bg-white rounded shadow hover:bg-gray-50" title="Duplicate">
                                            <i class="bi bi-copy text-gray-400"></i>
                                        </button>
                                        <button @click.stop="deleteBlock(element.id)" class="p-1.5 bg-white rounded shadow hover:bg-red-50" title="Delete">
                                            <i class="bi bi-trash text-red-400"></i>
                                        </button>
                                    </div>

                                    <!-- Block Label -->
                                    <div class="absolute top-2 left-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-xs bg-gray-800 text-white px-2 py-1 rounded">
                                            {{ getBlockLabel(element.type) }}
                                        </span>
                                    </div>

                                    <!-- Block Preview -->
                                    <component
                                        :is="previewComponents[element.type]"
                                        :block="element"
                                        class="pointer-events-none"
                                    />
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <!-- Helper Text -->
                    <p class="text-center text-xs text-gray-400 mt-4">
                        <i class="bi bi-info-circle me-1"></i>
                        This {{ sectionType }} will appear on all pages of your website
                    </p>
                </div>
            </div>

            <!-- Right: Block Editor OR Version Panel -->
            <div class="w-80 bg-white border-l overflow-y-auto">
                <!-- Version History Panel -->
                <div v-if="showVersionPanel" class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">
                            <i class="bi bi-clock-history me-2"></i>Version History
                        </h3>
                        <button @click="showVersionPanel = false" class="text-gray-400 hover:text-gray-600">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div v-if="loadingVersions" class="text-center py-8">
                        <i class="bi bi-arrow-repeat animate-spin text-2xl text-gray-400"></i>
                    </div>

                    <div v-else-if="versionHistory.length === 0" class="text-center py-8 text-gray-400">
                        <i class="bi bi-clock text-3xl mb-2"></i>
                        <p class="text-sm">No version history yet</p>
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="version in versionHistory"
                            :key="version.id"
                            :class="[
                                'p-3 rounded-lg border cursor-pointer transition-colors',
                                selectedVersionId === version.id ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300'
                            ]"
                            @click="loadVersion(version.id)"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium text-gray-800">
                                    Version {{ version.version_number }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <span v-if="version.is_published" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">
                                        Published
                                    </span>
                                    <span v-if="version.is_draft" class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded">
                                        Draft
                                    </span>
                                </div>
                            </div>
                            <p v-if="version.change_note" class="text-sm text-gray-600 mb-1">
                                {{ version.change_note }}
                            </p>
                            <div class="text-xs text-gray-400">
                                {{ formatDate(version.created_at) }} by {{ version.created_by }}
                            </div>

                            <!-- Version Actions -->
                            <div v-if="selectedVersionId === version.id && !version.is_draft" class="mt-2 pt-2 border-t flex gap-2">
                                <button
                                    @click.stop="rollbackToVersion(version.id)"
                                    class="flex-1 px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                                >
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Block Editor Panel -->
                <div v-else-if="selectedBlock" class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">
                            {{ getBlockLabel(selectedBlock.type) }}
                        </h3>
                        <button @click="selectedBlockId = null" class="text-gray-400 hover:text-gray-600">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <component
                        :is="editorComponents[selectedBlock.type]"
                        :block="selectedBlock"
                        @update:data="updateBlockData"
                        @update:settings="updateBlockSettings"
                    />
                </div>

                <div v-else class="p-6 text-center text-gray-400">
                    <i class="bi bi-cursor text-3xl mb-2"></i>
                    <p class="text-sm">Select a block to edit its settings</p>
                </div>
            </div>
        </div>

        <!-- Change Note Modal -->
        <div v-if="showChangeNoteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold mb-4">Save New Version</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Add an optional note describing your changes. This helps track what was modified in each version.
                </p>
                <textarea
                    v-model="changeNote"
                    placeholder="e.g., Updated navigation links, Added new announcement..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 mb-4"
                    rows="3"
                ></textarea>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showChangeNoteModal = false"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                    >
                        Cancel
                    </button>
                    <button
                        @click="save"
                        class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700"
                    >
                        Save Version
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.global-section-builder {
    min-height: 100vh;
}
</style>
