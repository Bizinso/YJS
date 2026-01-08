<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 })
let countdownInterval = null

const bannerStyle = computed(() => {
    const style = {}
    if (data.value.background_image) {
        style.backgroundImage = `url(${data.value.background_image})`
        style.backgroundSize = 'cover'
        style.backgroundPosition = 'center'
    }
    if (data.value.background_color) {
        style.backgroundColor = data.value.background_color
    }
    return style
})

const updateCountdown = () => {
    const endDate = data.value.end_date ? new Date(data.value.end_date) : null
    if (!endDate) return

    const now = new Date()
    const diff = endDate - now

    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 }
        if (countdownInterval) clearInterval(countdownInterval)
        return
    }

    countdown.value = {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((diff % (1000 * 60)) / 1000)
    }
}

onMounted(() => {
    if (settings.value.show_countdown && data.value.end_date) {
        updateCountdown()
        countdownInterval = setInterval(updateCountdown, 1000)
    }
})

onUnmounted(() => {
    if (countdownInterval) clearInterval(countdownInterval)
})
</script>

<template>
    <section class="offer-banner-block py-16 text-white" :style="bannerStyle">
        <div class="container mx-auto px-4 text-center">
            <span v-if="data.badge" class="inline-block px-4 py-1 bg-amber-500 text-sm font-bold rounded-full mb-4">
                {{ data.badge }}
            </span>
            <h2 v-if="data.title" class="text-4xl md:text-5xl font-bold mb-4">{{ data.title }}</h2>
            <p v-if="data.description" class="text-lg md:text-xl mb-6 opacity-90 max-w-2xl mx-auto">
                {{ data.description }}
            </p>

            <!-- Countdown -->
            <div v-if="settings.show_countdown && data.end_date" class="flex justify-center gap-4 mb-8">
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 min-w-[80px]">
                    <div class="text-3xl font-bold">{{ countdown.days }}</div>
                    <div class="text-sm opacity-80">Days</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 min-w-[80px]">
                    <div class="text-3xl font-bold">{{ countdown.hours }}</div>
                    <div class="text-sm opacity-80">Hours</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 min-w-[80px]">
                    <div class="text-3xl font-bold">{{ countdown.minutes }}</div>
                    <div class="text-sm opacity-80">Mins</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 min-w-[80px]">
                    <div class="text-3xl font-bold">{{ countdown.seconds }}</div>
                    <div class="text-sm opacity-80">Secs</div>
                </div>
            </div>

            <!-- Coupon Code -->
            <div v-if="data.coupon_code" class="mb-6">
                <p class="text-sm mb-2">Use code:</p>
                <code class="inline-block px-6 py-2 bg-white text-gray-800 font-mono font-bold text-lg rounded border-2 border-dashed border-amber-500">
                    {{ data.coupon_code }}
                </code>
            </div>

            <a
                v-if="data.button_text"
                :href="data.button_url || '#'"
                class="inline-block px-8 py-3 bg-white text-gray-800 font-bold rounded-lg hover:bg-gray-100 transition-colors"
            >
                {{ data.button_text }}
            </a>
        </div>
    </section>
</template>
