<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap items-center justify-between gap-2 mt-4 pt-4 border-t border-gray-100">
        <p class="text-xs text-gray-500">
            Affichage de <span class="font-semibold text-gray-700">{{ from || 0 }}</span> à <span class="font-semibold text-gray-700">{{ to || 0 }}</span> sur <span class="font-semibold text-gray-700">{{ total || 0 }}</span> résultats
        </p>

        <div class="flex items-center gap-1">
            <template v-for="(link, key) in links" :key="key">
                <div
                    v-if="link.url === null"
                    class="px-3 py-1 text-xs text-gray-400 bg-gray-50 rounded-lg border border-gray-200 cursor-not-allowed select-none"
                    v-html="cleanLabel(link.label)"
                />
                <Link
                    v-else
                    :href="link.url"
                    :class="[
                        'px-3 py-1 text-xs font-semibold rounded-lg border transition-colors',
                        link.active
                            ? 'bg-[#1B6B4A] text-white border-[#1B6B4A]'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                    ]"
                    v-html="cleanLabel(link.label)"
                    preserve-scroll
                    preserve-state
                />
            </template>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    links: {
        type: Array,
        default: () => []
    },
    from: Number,
    to: Number,
    total: Number,
})

function cleanLabel(label) {
    if (!label) return ''
    if (label.includes('Previous') || label.includes('Précédent')) return '&laquo; Précédent'
    if (label.includes('Next') || label.includes('Suivant')) return 'Suivant &raquo;'
    return label
}
</script>
