<script setup>
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<template>
    <footer class="bg-white border-t border-gray-200 py-4 px-6">
        <div class="flex items-center justify-between text-sm text-gray-500">
            <p>&copy; {{ currentYear }} YJS Jewellers. All rights reserved.</p>
            <div class="flex items-center space-x-4">
                <a href="#" class="hover:text-gray-700">Privacy Policy</a>
                <a href="#" class="hover:text-gray-700">Terms of Service</a>
                <a href="#" class="hover:text-gray-700">Support</a>
            </div>
        </div>
    </footer>
</template>
