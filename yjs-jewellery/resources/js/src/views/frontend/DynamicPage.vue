<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

// Frontend block components
import HeroBlock from './cms-blocks/HeroBlock.vue'
import RichTextBlock from './cms-blocks/RichTextBlock.vue'
import SpacerBlock from './cms-blocks/SpacerBlock.vue'
import GalleryBlock from './cms-blocks/GalleryBlock.vue'
import VideoBlock from './cms-blocks/VideoBlock.vue'
import ProductShowcaseBlock from './cms-blocks/ProductShowcaseBlock.vue'
import CategoryGridBlock from './cms-blocks/CategoryGridBlock.vue'
import OfferBannerBlock from './cms-blocks/OfferBannerBlock.vue'
import CTABlock from './cms-blocks/CTABlock.vue'
import TestimonialsBlock from './cms-blocks/TestimonialsBlock.vue'
import ContactFormBlock from './cms-blocks/ContactFormBlock.vue'
import FAQBlock from './cms-blocks/FAQBlock.vue'
import CustomHtmlBlock from './cms-blocks/CustomHtmlBlock.vue'

const route = useRoute()

const loading = ref(true)
const error = ref(null)
const page = ref(null)

const blockComponents = {
    hero: HeroBlock,
    rich_text: RichTextBlock,
    spacer: SpacerBlock,
    gallery: GalleryBlock,
    video: VideoBlock,
    product_showcase: ProductShowcaseBlock,
    category_grid: CategoryGridBlock,
    offer_banner: OfferBannerBlock,
    cta: CTABlock,
    testimonials: TestimonialsBlock,
    contact_form: ContactFormBlock,
    faq: FAQBlock,
    custom_html: CustomHtmlBlock,
}

const visibleBlocks = computed(() => {
    if (!page.value?.blocks) return []
    return page.value.blocks.filter(b => b.visible !== false)
})

const fetchPage = async (slug) => {
    loading.value = true
    error.value = null

    try {
        const endpoint = slug === 'homepage' ? '/api/pages/homepage' : `/api/pages/${slug}`
        const { data } = await axios.get(endpoint)

        if (data.success) {
            page.value = data.data

            // Set page meta
            if (page.value.seo) {
                document.title = page.value.seo.meta_title || page.value.title || 'YJS Jewellery'

                // Meta description
                let metaDesc = document.querySelector('meta[name="description"]')
                if (!metaDesc) {
                    metaDesc = document.createElement('meta')
                    metaDesc.name = 'description'
                    document.head.appendChild(metaDesc)
                }
                metaDesc.content = page.value.seo.meta_description || ''

                // OG tags
                updateMetaTag('og:title', page.value.seo.meta_title || page.value.title)
                updateMetaTag('og:description', page.value.seo.meta_description)
                if (page.value.seo.og_image) {
                    updateMetaTag('og:image', page.value.seo.og_image)
                }
            }

            // Track page view
            trackPageView()
        } else {
            error.value = 'Page not found'
        }
    } catch (err) {
        console.error('Failed to fetch page:', err)
        error.value = err.response?.status === 404 ? 'Page not found' : 'Failed to load page'
    } finally {
        loading.value = false
    }
}

const updateMetaTag = (property, content) => {
    if (!content) return
    let tag = document.querySelector(`meta[property="${property}"]`)
    if (!tag) {
        tag = document.createElement('meta')
        tag.setAttribute('property', property)
        document.head.appendChild(tag)
    }
    tag.content = content
}

const trackPageView = async () => {
    if (!page.value?.id) return
    try {
        await axios.post(`/api/pages/${page.value.id}/track-view`)
    } catch {
        // Silently fail
    }
}

const getBlockStyle = (block) => {
    const style = block.style || {}
    const css = {}

    if (style.backgroundColor) {
        css.backgroundColor = style.backgroundColor
    }
    if (style.padding) {
        css.paddingTop = (style.padding.top || 0) + 'px'
        css.paddingBottom = (style.padding.bottom || 0) + 'px'
    }

    return css
}

// Watch for route changes
watch(() => route.params.slug, (newSlug) => {
    if (newSlug) {
        fetchPage(newSlug)
    }
}, { immediate: false })

onMounted(() => {
    const slug = route.params.slug || 'homepage'
    fetchPage(slug)
})
</script>

<template>
    <div class="dynamic-page">
        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="mt-4 text-gray-500">Loading...</p>
            </div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <i class="bi bi-exclamation-circle text-5xl text-gray-300 mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ error }}</h1>
                <p class="text-gray-500 mb-4">The page you're looking for doesn't exist or has been moved.</p>
                <router-link to="/" class="text-amber-600 hover:underline">
                    Go back home
                </router-link>
            </div>
        </div>

        <!-- Page Content -->
        <div v-else-if="page">
            <component
                v-for="block in visibleBlocks"
                :key="block.id"
                :is="blockComponents[block.type]"
                :block="block"
                :page-id="page.id"
                :style="getBlockStyle(block)"
                :class="[
                    'block-' + block.type,
                    block.animation?.type && 'animate-' + block.animation.type
                ]"
            />
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fadeIn {
    animation: fadeIn 0.5s ease-out;
}

.animate-slideUp {
    animation: slideUp 0.5s ease-out;
}

.animate-slideDown {
    animation: slideDown 0.5s ease-out;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
