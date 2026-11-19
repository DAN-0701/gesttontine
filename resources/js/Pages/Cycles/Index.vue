<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Cycles</h1>
                    <p class="text-sm text-gray-500 mt-1">Gestion des rotations de tontine.</p>
                </div>
                <Link
                    v-if="canCreateCycle"
                    href="/cycles/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors cursor-pointer self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Nouveau cycle</span>
                </Link>
            </div>

            <!-- Liste des cycles -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="c in cycles"
                    :key="c.id"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4 hover:border-gray-200 transition-all flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ c.libelle }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ c.membres_count }} membre(s) · {{ formatMoney(c.montant_cotisation) }} FCFA / {{ c.periodicite }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold shrink-0',
                                    c.statut === 'actif'
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : c.statut === 'brouillon'
                                        ? 'bg-amber-50 text-amber-700'
                                        : 'bg-gray-100 text-gray-600'
                                ]"
                            >
                                {{ c.statut === 'actif' ? 'En cours' : (c.statut === 'brouillon' ? 'Brouillon' : 'Terminé') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mt-4 pt-3 border-t border-gray-50">
                            <div>
                                <span class="text-gray-400">Date début :</span>
                                <span class="font-medium ml-1 text-gray-800">{{ formatDate(c.date_debut) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Date fin :</span>
                                <span class="font-medium ml-1 text-gray-800">{{ formatDate(c.date_fin) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Tour actuel :</span>
                                <span class="font-medium ml-1 text-gray-800">{{ c.tour_actuel || 1 }} / {{ c.total_tours || 1 }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Tours distribués :</span>
                                <span class="font-medium ml-1 text-gray-800">{{ c.tours_distribues || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions avec confirmation card (Demande 4) -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-2">
                        <Link
                            :href="`/cycles/${c.id}`"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B6B4A] hover:underline"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Voir le tableau</span>
                        </Link>

                        <div class="flex items-center gap-2">
                            <!-- Démarrer si brouillon -->
                            <button
                                v-if="canStartCycle && c.statut === 'brouillon'"
                                type="button"
                                @click="askDemarrerCycle(c)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-xs font-semibold rounded-lg shadow-2xs transition-colors cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Démarrer</span>
                            </button>

                            <!-- Clôturer si actif -->
                            <button
                                v-if="canCloseCycle && c.statut === 'actif'"
                                type="button"
                                @click="askCloturerCycle(c)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Clôturer</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!cycles || cycles.length === 0" class="col-span-2 py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
                    Aucun cycle créé pour le moment.
                </div>
            </div>

            <!-- Confirmation Modal Card (Demande 4) -->
            <ConfirmModal
                :show="confirmData.show"
                :title="confirmData.title"
                :message="confirmData.message"
                :confirmText="confirmData.confirmText"
                :isDanger="confirmData.isDanger"
                @confirm="handleConfirmAction"
                @cancel="confirmData.show = false"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    cycles: {
        type: Array,
        default: () => []
    }
})

const page = usePage()

const canCreateCycle = computed(() => {
    if (page.props.auth?.user?.permissions) {
        return !!page.props.auth.user.permissions.creer_cycle
    }
    const role = page.props.auth?.user?.role
    return role === 'administrateur' || role === 'gestionnaire'
})

const canStartCycle = computed(() => {
    if (page.props.auth?.user?.permissions) {
        return !!page.props.auth.user.permissions.demarrer_cycle
    }
    const role = page.props.auth?.user?.role
    return role === 'administrateur' || role === 'gestionnaire'
})

const canCloseCycle = computed(() => {
    if (page.props.auth?.user?.permissions) {
        return !!page.props.auth.user.permissions.cloturer_cycle
    }
    const role = page.props.auth?.user?.role
    return role === 'administrateur' || role === 'gestionnaire'
})

// Confirmation Card (Demande 4)
const confirmData = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirmer',
    isDanger: false,
    action: null,
})

function askDemarrerCycle(cycle) {
    confirmData.value = {
        show: true,
        title: 'Démarrer le cycle',
        message: `Voulez-vous démarrer le cycle « ${cycle.libelle} » ? Les tours et les cotisations seront immédiatement générés.`,
        confirmText: 'Démarrer le cycle',
        isDanger: false,
        action: () => {
            router.post(`/cycles/${cycle.id}/demarrer`)
        }
    }
}

function askCloturerCycle(cycle) {
    confirmData.value = {
        show: true,
        title: 'Clôturer le cycle',
        message: `Voulez-vous vraiment clôturer définitivement le cycle « ${cycle.libelle} » ?`,
        confirmText: 'Clôturer définitivement',
        isDanger: true,
        action: () => {
            router.post(`/cycles/${cycle.id}/cloturer`)
        }
    }
}

function handleConfirmAction() {
    if (confirmData.value.action) {
        confirmData.value.action()
    }
    confirmData.value.show = false
}

function formatMoney(amount) {
    if (amount === undefined || amount === null) return '0'
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}
</script>
