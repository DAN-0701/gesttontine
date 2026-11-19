<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs animate-in fade-in duration-150"
        @click.self="cancel"
    >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <div class="flex items-start gap-3">
                <div
                    :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center shrink-0',
                        isDanger ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-[#1B6B4A]'
                    ]"
                >
                    <svg v-if="isDanger" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">{{ title }}</h3>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed">{{ message }}</p>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                <button
                    type="button"
                    @click="cancel"
                    class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer"
                >
                    {{ cancelText }}
                </button>
                <button
                    type="button"
                    @click="confirm"
                    :class="[
                        'px-4 py-2 text-sm font-semibold text-white rounded-lg transition-colors cursor-pointer flex items-center gap-2',
                        isDanger ? 'bg-red-600 hover:bg-red-700' : 'bg-[#1B6B4A] hover:bg-[#155A3E]'
                    ]"
                >
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Confirmation requise'
    },
    message: {
        type: String,
        default: 'Êtes-vous sûr de vouloir effectuer cette action ?'
    },
    confirmText: {
        type: String,
        default: 'Confirmer'
    },
    cancelText: {
        type: String,
        default: 'Annuler'
    },
    isDanger: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['confirm', 'cancel'])

function confirm() {
    emit('confirm')
}

function cancel() {
    emit('cancel')
}
</script>
