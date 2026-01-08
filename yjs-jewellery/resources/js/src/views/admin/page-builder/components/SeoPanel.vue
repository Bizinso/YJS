<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { usePageBuilder } from '@/composables/usePageBuilder'
import axiosAdmin from '@/plugins/axiosAdmin'

const { page, updatePageSeo, showSeoPanel } = usePageBuilder()

const analyzing = ref(false)
const seoAnalysis = ref(null)
const activeTab = ref('settings')

// Computed
const seo = computed(() => page.value?.seo || {})

const titleLength = computed(() => (seo.value.meta_title || '').length)
const descriptionLength = computed(() => (seo.value.meta_description || '').length)

const titleStatus = computed(() => {
    if (titleLength.value === 0) return { color: 'gray', text: 'Not set' }
    if (titleLength.value < 30) return { color: 'yellow', text: 'Too short' }
    if (titleLength.value > 60) return { color: 'red', text: 'Too long' }
    return { color: 'green', text: 'Good' }
})

const descriptionStatus = computed(() => {
    if (descriptionLength.value === 0) return { color: 'gray', text: 'Not set' }
    if (descriptionLength.value < 120) return { color: 'yellow', text: 'Too short' }
    if (descriptionLength.value > 160) return { color: 'red', text: 'Too long' }
    return { color: 'green', text: 'Good' }
})

const previewUrl = computed(() => {
    const baseUrl = window.location.origin
    return `${baseUrl}/${page.value?.slug || 'page-url'}`
})

const scoreColor = computed(() => {
    const score = seoAnalysis.value?.score || 0
    if (score >= 80) return 'text-green-600'
    if (score >= 60) return 'text-yellow-600'
    if (score >= 40) return 'text-orange-600'
    return 'text-red-600'
})

const scoreLabel = computed(() => {
    const score = seoAnalysis.value?.score || 0
    if (score >= 80) return 'Good'
    if (score >= 60) return 'Needs Improvement'
    if (score >= 40) return 'Poor'
    return 'Critical'
})

const criticalIssues = computed(() =>
    (seoAnalysis.value?.issues || []).filter(i => i.severity === 'critical')
)

const warningIssues = computed(() =>
    (seoAnalysis.value?.issues || []).filter(i => i.severity === 'warning')
)

const infoIssues = computed(() =>
    (seoAnalysis.value?.issues || []).filter(i => i.severity === 'info')
)

// Methods
const updateField = (field, value) => {
    updatePageSeo({ [field]: value })
}

const analyzeSeo = async () => {
    if (!page.value?.id) return

    analyzing.value = true
    try {
        const { data } = await axiosAdmin.get(`/cms/pages/${page.value.id}/seo-analyze`)
        if (data.success) {
            seoAnalysis.value = data.data
        }
    } catch (error) {
        console.error('SEO analysis failed:', error)
    } finally {
        analyzing.value = false
    }
}

// Auto-analyze on mount if page exists
onMounted(() => {
    if (page.value?.id) {
        analyzeSeo()
    }
})

// Re-analyze when page changes
watch(() => page.value?.id, (newId) => {
    if (newId) {
        analyzeSeo()
    }
})
</script>

<template>
    <div class="h-full flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="bi bi-search text-amber-500"></i>
                <span class="font-semibold text-gray-800">SEO Settings</span>
            </div>
            <button
                @click="showSeoPanel = false"
                class="p-1 hover:bg-gray-100 rounded text-gray-400"
                title="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Tabs -->
        <div class="flex border-b">
            <button
                @click="activeTab = 'settings'"
                :class="[
                    'flex-1 px-4 py-2 text-sm font-medium transition-colors',
                    activeTab === 'settings'
                        ? 'text-amber-600 border-b-2 border-amber-600'
                        : 'text-gray-500 hover:text-gray-700'
                ]"
            >
                <i class="bi bi-gear me-1"></i> Settings
            </button>
            <button
                @click="activeTab = 'analysis'"
                :class="[
                    'flex-1 px-4 py-2 text-sm font-medium transition-colors',
                    activeTab === 'analysis'
                        ? 'text-amber-600 border-b-2 border-amber-600'
                        : 'text-gray-500 hover:text-gray-700'
                ]"
            >
                <i class="bi bi-graph-up me-1"></i> Analysis
                <span v-if="seoAnalysis?.issues?.length" class="ml-1 bg-red-100 text-red-600 text-xs px-1.5 py-0.5 rounded-full">
                    {{ seoAnalysis.issues.length }}
                </span>
            </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Settings Tab -->
            <div v-if="activeTab === 'settings'" class="p-4 space-y-6">
                <!-- Google Preview -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Google Preview</label>
                    <div class="p-4 bg-white border rounded-lg">
                        <div class="text-blue-600 text-lg hover:underline cursor-pointer truncate">
                            {{ seo.meta_title || page?.title || 'Page Title' }}
                        </div>
                        <div class="text-green-700 text-sm truncate">
                            {{ previewUrl }}
                        </div>
                        <div class="text-gray-600 text-sm mt-1 line-clamp-2">
                            {{ seo.meta_description || 'Add a meta description to appear in search results...' }}
                        </div>
                    </div>
                </div>

                <!-- Meta Title -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Meta Title</label>
                        <span :class="[
                            'text-xs px-2 py-0.5 rounded',
                            titleStatus.color === 'green' ? 'bg-green-100 text-green-700' :
                            titleStatus.color === 'yellow' ? 'bg-yellow-100 text-yellow-700' :
                            titleStatus.color === 'red' ? 'bg-red-100 text-red-700' :
                            'bg-gray-100 text-gray-500'
                        ]">
                            {{ titleLength }}/60 - {{ titleStatus.text }}
                        </span>
                    </div>
                    <input
                        type="text"
                        :value="seo.meta_title"
                        @input="updateField('meta_title', $event.target.value)"
                        placeholder="Page title for search engines"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    >
                    <!-- Progress bar -->
                    <div class="mt-2 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            :class="[
                                'h-full transition-all',
                                titleLength <= 60 ? 'bg-green-500' : 'bg-red-500'
                            ]"
                            :style="{ width: Math.min(titleLength / 60 * 100, 100) + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Meta Description -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Meta Description</label>
                        <span :class="[
                            'text-xs px-2 py-0.5 rounded',
                            descriptionStatus.color === 'green' ? 'bg-green-100 text-green-700' :
                            descriptionStatus.color === 'yellow' ? 'bg-yellow-100 text-yellow-700' :
                            descriptionStatus.color === 'red' ? 'bg-red-100 text-red-700' :
                            'bg-gray-100 text-gray-500'
                        ]">
                            {{ descriptionLength }}/160 - {{ descriptionStatus.text }}
                        </span>
                    </div>
                    <textarea
                        :value="seo.meta_description"
                        @input="updateField('meta_description', $event.target.value)"
                        placeholder="Brief description for search results"
                        rows="3"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    ></textarea>
                    <!-- Progress bar -->
                    <div class="mt-2 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            :class="[
                                'h-full transition-all',
                                descriptionLength <= 160 ? 'bg-green-500' : 'bg-red-500'
                            ]"
                            :style="{ width: Math.min(descriptionLength / 160 * 100, 100) + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Focus Keyword -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Focus Keyword</label>
                    <input
                        type="text"
                        :value="seo.focus_keyword"
                        @input="updateField('focus_keyword', $event.target.value)"
                        placeholder="Primary keyword for this page"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    >
                    <p class="mt-1 text-xs text-gray-500">The main keyword you want to rank for</p>
                </div>

                <!-- OG Image -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Social Share Image</label>
                    <input
                        type="text"
                        :value="seo.og_image"
                        @input="updateField('og_image', $event.target.value)"
                        placeholder="https://example.com/image.jpg"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    >
                    <p class="mt-1 text-xs text-gray-500">Recommended: 1200x630 pixels</p>
                </div>

                <!-- Canonical URL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Canonical URL</label>
                    <input
                        type="text"
                        :value="seo.canonical_url"
                        @input="updateField('canonical_url', $event.target.value)"
                        :placeholder="previewUrl"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    >
                    <p class="mt-1 text-xs text-gray-500">Leave empty to use page URL</p>
                </div>

                <!-- Robots -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Engine Indexing</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="seo.noindex !== true"
                                @change="updateField('noindex', !$event.target.checked)"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">Allow indexing (appear in search)</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="seo.nofollow !== true"
                                @change="updateField('nofollow', !$event.target.checked)"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">Follow links on this page</span>
                        </label>
                    </div>
                </div>

                <!-- Schema Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Schema Type</label>
                    <select
                        :value="seo.schema_type || 'WebPage'"
                        @change="updateField('schema_type', $event.target.value)"
                        class="w-full px-3 py-2 border rounded-lg text-sm"
                    >
                        <option value="WebPage">Web Page</option>
                        <option value="Article">Article</option>
                        <option value="Product">Product</option>
                        <option value="FAQPage">FAQ Page</option>
                        <option value="ContactPage">Contact Page</option>
                        <option value="AboutPage">About Page</option>
                    </select>
                </div>
            </div>

            <!-- Analysis Tab -->
            <div v-else class="p-4">
                <!-- Score Card -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg text-center">
                    <div class="flex items-center justify-center mb-2">
                        <div class="relative">
                            <svg class="w-20 h-20" viewBox="0 0 36 36">
                                <circle
                                    cx="18" cy="18" r="16"
                                    fill="none"
                                    stroke="#e5e7eb"
                                    stroke-width="3"
                                />
                                <circle
                                    cx="18" cy="18" r="16"
                                    fill="none"
                                    :stroke="seoAnalysis?.score >= 80 ? '#22c55e' : seoAnalysis?.score >= 60 ? '#eab308' : seoAnalysis?.score >= 40 ? '#f97316' : '#ef4444'"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${(seoAnalysis?.score || 0)} 100`"
                                    transform="rotate(-90 18 18)"
                                />
                            </svg>
                            <span :class="['absolute inset-0 flex items-center justify-center text-xl font-bold', scoreColor]">
                                {{ seoAnalysis?.score || '-' }}
                            </span>
                        </div>
                    </div>
                    <p :class="['font-semibold', scoreColor]">{{ scoreLabel }}</p>
                    <p class="text-xs text-gray-500 mt-1">SEO Score</p>

                    <button
                        @click="analyzeSeo"
                        :disabled="analyzing"
                        class="mt-3 px-4 py-1.5 text-sm bg-amber-600 text-white rounded hover:bg-amber-700 disabled:opacity-50"
                    >
                        <i v-if="analyzing" class="bi bi-arrow-repeat animate-spin me-1"></i>
                        <i v-else class="bi bi-arrow-clockwise me-1"></i>
                        {{ analyzing ? 'Analyzing...' : 'Re-analyze' }}
                    </button>
                </div>

                <!-- Issues List -->
                <div v-if="seoAnalysis" class="space-y-4">
                    <!-- Critical Issues -->
                    <div v-if="criticalIssues.length">
                        <h4 class="text-sm font-semibold text-red-600 mb-2 flex items-center">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            Critical Issues ({{ criticalIssues.length }})
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(issue, i) in criticalIssues"
                                :key="'c'+i"
                                class="p-3 bg-red-50 border border-red-200 rounded-lg"
                            >
                                <p class="font-medium text-red-800 text-sm">{{ issue.title }}</p>
                                <p class="text-red-600 text-xs mt-1">{{ issue.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Warnings -->
                    <div v-if="warningIssues.length">
                        <h4 class="text-sm font-semibold text-yellow-600 mb-2 flex items-center">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Warnings ({{ warningIssues.length }})
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(issue, i) in warningIssues"
                                :key="'w'+i"
                                class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg"
                            >
                                <p class="font-medium text-yellow-800 text-sm">{{ issue.title }}</p>
                                <p class="text-yellow-600 text-xs mt-1">{{ issue.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Info -->
                    <div v-if="infoIssues.length">
                        <h4 class="text-sm font-semibold text-blue-600 mb-2 flex items-center">
                            <i class="bi bi-info-circle me-1"></i>
                            Info ({{ infoIssues.length }})
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(issue, i) in infoIssues"
                                :key="'i'+i"
                                class="p-3 bg-blue-50 border border-blue-200 rounded-lg"
                            >
                                <p class="font-medium text-blue-800 text-sm">{{ issue.title }}</p>
                                <p class="text-blue-600 text-xs mt-1">{{ issue.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Suggestions -->
                    <div v-if="seoAnalysis.suggestions?.length">
                        <h4 class="text-sm font-semibold text-gray-600 mb-2 flex items-center">
                            <i class="bi bi-lightbulb me-1"></i>
                            Suggestions ({{ seoAnalysis.suggestions.length }})
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(suggestion, i) in seoAnalysis.suggestions"
                                :key="'s'+i"
                                class="p-3 bg-gray-50 border border-gray-200 rounded-lg"
                            >
                                <p class="font-medium text-gray-800 text-sm">{{ suggestion.title }}</p>
                                <p class="text-gray-600 text-xs mt-1">{{ suggestion.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- All Good -->
                    <div
                        v-if="!criticalIssues.length && !warningIssues.length && !seoAnalysis.suggestions?.length"
                        class="text-center py-8"
                    >
                        <i class="bi bi-check-circle text-4xl text-green-500 mb-2"></i>
                        <p class="text-green-600 font-medium">Great job!</p>
                        <p class="text-gray-500 text-sm">No major SEO issues found.</p>
                    </div>
                </div>

                <!-- Loading -->
                <div v-else-if="analyzing" class="text-center py-8">
                    <i class="bi bi-arrow-repeat animate-spin text-3xl text-amber-500"></i>
                    <p class="text-gray-500 mt-2">Analyzing SEO...</p>
                </div>

                <!-- No Analysis -->
                <div v-else class="text-center py-8">
                    <i class="bi bi-graph-up text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">Save the page to run SEO analysis</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
