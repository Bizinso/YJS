<script setup>
import { computed } from 'vue'

const props = defineProps({
    block: { type: Object, required: true }
})

const data = props.block.data || {}
const settings = props.block.settings || {}

const aspectRatioClass = computed(() => {
    const ratio = settings.aspect_ratio || '16:9'
    switch (ratio) {
        case '4:3': return 'aspect-[4/3]'
        case '1:1': return 'aspect-square'
        case '21:9': return 'aspect-[21/9]'
        default: return 'aspect-video'
    }
})

const youtubeId = computed(() => {
    if (!data.video_url) return null
    const match = data.video_url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&?/]+)/)
    return match ? match[1] : null
})

const vimeoId = computed(() => {
    if (!data.video_url) return null
    const match = data.video_url.match(/vimeo\.com\/(\d+)/)
    return match ? match[1] : null
})
</script>

<template>
    <div>
        <!-- Header -->
        <div v-if="data.title" class="text-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">{{ data.title }}</h2>
        </div>

        <!-- Video Container -->
        <div :class="['relative bg-gray-900 rounded-lg overflow-hidden', aspectRatioClass]">
            <!-- YouTube Embed -->
            <iframe
                v-if="data.video_type === 'youtube' && youtubeId"
                :src="`https://www.youtube.com/embed/${youtubeId}?autoplay=0&controls=${settings.controls ? 1 : 0}`"
                class="absolute inset-0 w-full h-full"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>

            <!-- Vimeo Embed -->
            <iframe
                v-else-if="data.video_type === 'vimeo' && vimeoId"
                :src="`https://player.vimeo.com/video/${vimeoId}`"
                class="absolute inset-0 w-full h-full"
                frameborder="0"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen
            ></iframe>

            <!-- Self-hosted or Placeholder -->
            <div v-else class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-gray-400">
                    <i class="bi bi-play-circle text-5xl"></i>
                    <p class="mt-2">{{ data.video_url ? 'Video Preview' : 'Add a video URL' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
