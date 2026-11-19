<template>
    <div
        v-if="progress > 0"
        class="fixed top-0 left-0 right-0 h-1 bg-transparent z-[9999] pointer-events-none"
    >
        <div
            class="h-full bg-[#1B6B4A] transition-all duration-300 ease-out shadow-xs"
            :style="{ width: `${progress}%` }"
        ></div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const progress = ref(0)
let timer = null

function startProgress() {
    progress.value = 15
    if (timer) clearInterval(timer)
    timer = setInterval(() => {
        if (progress.value < 85) {
            progress.value += Math.floor(Math.random() * 12) + 5
        }
    }, 200)
}

function finishProgress() {
    progress.value = 100
    if (timer) clearInterval(timer)
    setTimeout(() => {
        progress.value = 0
    }, 300)
}

let removeStartListener = null
let removeFinishListener = null

onMounted(() => {
    removeStartListener = router.on('start', (event) => {
        // Déclencher pour les requêtes de mutation (POST, PUT, PATCH, DELETE)
        const method = (event.detail?.visit?.method || '').toUpperCase()
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            startProgress()
        }
    })

    removeFinishListener = router.on('finish', () => {
        finishProgress()
    })
})

onUnmounted(() => {
    if (removeStartListener) removeStartListener()
    if (removeFinishListener) removeFinishListener()
    if (timer) clearInterval(timer)
})
</script>
