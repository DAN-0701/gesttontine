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

            <!-- Filtres et Recherche de cycles (Demande 6) -->
            <div class="flex flex-col md:flex-row gap-4 items-end bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">Rechercher un cycle</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Nom ou libellé du cycle..."
                        @keyup.enter="handleSearch"
                        class="w-full px-3.5 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                    />
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">Filtrer par statut</label>
                    <select
                        v-model="statut"
                        @change="handleSearch"
                        class="w-full px-3.5 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                    >
                        <option value="tous">Tous les statuts</option>
                        <option value="brouillon">Brouillon</option>
                        <option value="actif">Actif (En cours)</option>
                        <option value="termine">Terminé</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="handleSearch"
                        class="w-full md:w-auto inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-800 text-sm font-semibold rounded-lg transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Rechercher</span>
                    </button>
                </div>
            </div>

            <!-- Liste des cycles -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="c in displayedCycles"
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
                            <!-- Modifier si brouillon -->
                            <Link
                                v-if="canCreateCycle && c.statut === 'brouillon'"
                                :href="`/cycles/${c.id}/edit`"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold rounded-lg border border-amber-200 transition-colors cursor-pointer"
                                title="Modifier ce cycle"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Modifier</span>
                            </Link>

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

                            <!-- Supprimer si brouillon -->
                            <button
                                v-if="canCreateCycle && c.statut === 'brouillon'"
                                type="button"
                                @click="askSupprimerCycle(c)"
                                class="inline-flex items-center gap-1 p-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg border border-red-200 transition-colors cursor-pointer"
                                title="Supprimer ce cycle brouillon"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!displayedCycles || displayedCycles.length === 0" class="col-span-2 py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
                    Aucun cycle trouvé pour le moment.
                </div>
            </div>

            <!-- Pagination (Demande 4 : 5 par page) -->
            <Pagination
                v-if="paginationLinks.length"
                :links="paginationLinks"
                :from="cyclesPagination?.from"
                :to="cyclesPagination?.to"
                :total="cyclesPagination?.total"
            />

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
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    cycles: {
        type: [Array, Object],
        default: () => []
    },
    filters: Object,
})

const page = usePage()

const search = ref(props.filters?.search || '')
const statut = ref(props.filters?.statut || 'tous')

const cyclesPagination = computed(() => {
    return Array.isArray(props.cycles) ? null : props.cycles
})

const paginationLinks = computed(() => {
    return cyclesPagination.value?.links || []
})

const displayedCycles = computed(() => {
    if (Array.isArray(props.cycles)) {
        return props.cycles
    }
    return props.cycles?.data || []
})

function handleSearch() {
    router.get('/cycles', {
        search: search.value,
        statut: statut.value
    }, { preserveState: true })
}

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

function askSupprimerCycle(cycle) {
    confirmData.value = {
        show: true,
        title: 'Supprimer le cycle',
        message: `Voulez-vous vraiment supprimer le cycle brouillon « ${cycle.libelle} » ? Cette action est irréversible.`,
        confirmText: 'Supprimer définitivement',
        isDanger: true,
        action: () => {
            router.delete(`/cycles/${cycle.id}`)
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
