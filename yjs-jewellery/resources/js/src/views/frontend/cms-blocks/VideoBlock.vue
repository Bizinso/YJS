<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const embedUrl = computed(() => {
    const url = data.value.video_url || ''
    const type = data.value.video_type || 'youtube'

    if (type === 'youtube') {
        const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/)
        if (match) {
            let embedUrl = `https://www.youtube.com/embed/${match[1]}?rel=0`
            if (settings.value.autoplay) embedUrl += '&autoplay=1&mute=1'
            if (settings.value.loop) embedUrl += '&loop=1'
            if (settings.value.controls === false) embedUrl += '&controls=0'
            return embedUrl
        }
    } else if (type === 'vimeo') {
        const match = url.match(/vimeo\.com\/(\d+)/)
        if (match) {
            let embedUrl = `https://player.vimeo.com/video/${match[1]}`
            if (settings.value.autoplay) embedUrl += '?autoplay=1&muted=1'
            return embedUrl
        }
    }
    return url
})

const aspectRatioClass = computed(() => {
    const ratio = settings.value.aspect_ratio || '16:9'
    return {
        '16:9': 'aspect-video',
        '4:3': 'aspect-4/3',
        '1:1': 'aspect-square',
        '21:9': 'aspect-[21/9]'
    }[ratio] || 'aspect-video'
})
</script>

<template>
    <section class="video-block py-12">
        <div class="container mx-auto px-4">
            <h2 v-if="data.title" class="text-2xl font-bold text-center mb-6">{{ data.title }}</h2>
            <div class="max-w-4xl mx-auto">
                <div :class="['relative overflow-hidden rounded-lg bg-gray-900', aspectRatioClass]">
                    <iframe
                        v-if="data.video_type !== 'self-hosted'"
                        :src="embedUrl"
                        class="absolute inset-0 w-full h-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                    <video
                        v-else
                        :src="data.video_url"
                        :poster="data.thumbnail"
                        :autoplay="settings.autoplay"
                        :loop="settings.loop"
                        :controls="settings.controls !== false"
                        class="absolute inset-0 w-full h-full object-cover"
                    ></video>
                </div>
            </div>
        </div>
    </section>
</template>
