<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header avec actions et boutons + icônes -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Membres</h1>
                    <p class="text-sm text-gray-500 mt-1">Les personnes inscrites dans la tontine.</p>
                </div>

                <div v-if="canCreateMember" class="flex items-center gap-3">
                    <!-- Bouton Importer / Télécharger Modèle Excel fusionné (Demande 2) -->
                    <button
                        type="button"
                        @click="showImportModal = true"
                        class="inline-flex items-center gap-2 px-3.5 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-800 text-sm font-semibold rounded-lg shadow-2xs transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>Importer des membres (Excel)</span>
                    </button>

                    <!-- Bouton Nouveau membre -->
                    <Link
                        href="/membres/create"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Nouveau membre</span>
                    </Link>
                </div>
            </div>

            <!-- Filtres -->
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">Rechercher par nom ou téléphone</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="ex: 0700 ou Kouadio"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                    />
                </div>
                <div class="w-full md:w-44">
                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">Statut</label>
                    <select
                        v-model="statut"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                    >
                        <option value="tous">Tous</option>
                        <option value="actif">Actif</option>
                        <option value="desactive">Désactivé</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="handleSearch"
                        class="w-full md:w-auto inline-flex items-center gap-2 px-5 py-2.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-800 text-sm font-semibold rounded-lg transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Rechercher</span>
                    </button>
                </div>
            </div>

            <!-- Tableau des membres -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <p v-if="search" class="text-sm text-gray-600 mb-4 font-medium">
                    {{ displayedMembres.length }} résultat(s) pour « {{ search }} »
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs border-b border-gray-100">
                                <th class="pb-3 font-semibold">Membre</th>
                                <th class="pb-3 font-semibold">Téléphone</th>
                                <th class="pb-3 font-semibold">Email</th>
                                <th class="pb-3 font-semibold">Adhésion</th>
                                <th class="pb-3 font-semibold">Statut</th>
                                <th class="pb-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="membre in displayedMembres"
                                :key="membre.id"
                                class="hover:bg-gray-50/50 transition-colors"
                            >
                                <td class="py-3.5 font-semibold text-gray-900">
                                    {{ membre.prenom }} {{ membre.nom }}
                                </td>
                                <td class="py-3.5 text-gray-600">{{ membre.telephone }}</td>
                                <td class="py-3.5 text-gray-600">{{ membre.email || '—' }}</td>
                                <td class="py-3.5 text-gray-600">{{ formatDate(membre.date_adhesion) }}</td>
                                <td class="py-3.5">
                                    <span
                                        :class="[
                                            'inline-block px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            membre.statut === 'actif'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-gray-100 text-gray-600'
                                        ]"
                                    >
                                        {{ membre.statut === 'actif' ? 'Actif' : 'Désactivé' }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Action Détail (Demande 1) -->
                                        <button
                                            type="button"
                                            @click="viewDetails(membre)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer"
                                            title="Voir tous les détails"
                                        >
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Détails</span>
                                        </button>

                                        <!-- Action Modifier (réservé gestionnaire) -->
                                        <Link
                                            v-if="canEditMember"
                                            :href="`/members/${membre.id}/edit`"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 hover:bg-emerald-100 text-[#1B6B4A] transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Modifier</span>
                                        </Link>

                                        <!-- Action Raccourci Activer / Désactiver (Demande 1) -->
                                        <button
                                            v-if="canEditMember"
                                            type="button"
                                            @click="askToggleStatut(membre)"
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors cursor-pointer',
                                                membre.statut === 'actif'
                                                    ? 'bg-amber-50 hover:bg-amber-100 text-amber-700'
                                                    : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'
                                            ]"
                                            :title="membre.statut === 'actif' ? 'Désactiver le membre' : 'Activer le membre'"
                                        >
                                            <svg v-if="membre.statut === 'actif'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ membre.statut === 'actif' ? 'Désactiver' : 'Activer' }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="displayedMembres.length === 0">
                                <td colspan="6" class="py-8 text-center text-gray-400">
                                    Aucun membre trouvé.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination (Demande 4 : 10 par page) -->
                <Pagination
                    v-if="paginationLinks.length"
                    :links="paginationLinks"
                    :from="membresPagination?.from"
                    :to="membresPagination?.to"
                    :total="membresPagination?.total"
                />
            </div>

            <!-- Modal Détails du membre (Demande 1) -->
            <div
                v-if="showDetailsModal && selectedMember"
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 backdrop-blur-xs animate-in fade-in duration-150"
                @click.self="showDetailsModal = false"
            >
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ selectedMember.prenom }} {{ selectedMember.nom }}
                            </h3>
                            <p class="text-xs text-gray-500">Fiche détaillée du membre</p>
                        </div>
                        <button
                            @click="showDetailsModal = false"
                            class="text-gray-400 hover:text-gray-600 font-bold p-1 text-sm"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Téléphone</p>
                            <p class="font-semibold text-gray-800 mt-0.5">{{ selectedMember.telephone }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Email</p>
                            <p class="font-semibold text-gray-800 mt-0.5">{{ selectedMember.email || 'Non renseigné' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Adresse</p>
                            <p class="font-semibold text-gray-800 mt-0.5">{{ selectedMember.adresse || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Date d'adhésion</p>
                            <p class="font-semibold text-gray-800 mt-0.5">{{ formatDate(selectedMember.date_adhesion) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Statut actuel</p>
                            <span
                                :class="[
                                    'inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold mt-1',
                                    selectedMember.statut === 'actif'
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-gray-100 text-gray-600'
                                ]"
                            >
                                {{ selectedMember.statut === 'actif' ? 'Actif' : 'Désactivé' }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex justify-end">
                        <button
                            type="button"
                            @click="showDetailsModal = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors cursor-pointer"
                        >
                            Fermer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Import Excel & Téléchargement Modèle (Demande 2) -->
            <div
                v-if="showImportModal"
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 backdrop-blur-xs animate-in fade-in duration-150"
                @click.self="showImportModal = false"
            >
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Importer des membres</h3>
                            <p class="text-xs text-gray-500">Créez votre fichier directement avec le modèle officiel ou téléversez-le</p>
                        </div>
                        <button
                            @click="showImportModal = false"
                            class="text-gray-400 hover:text-gray-600 font-bold p-1 text-sm cursor-pointer"
                        >
                            &times;
                        </button>
                    </div>

                    <!-- Étape 1 : Téléchargement du Modèle Excel pré-formaté -->
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold text-emerald-900">1. Obtenir le modèle avec les colonnes bien alignées</p>
                            <p class="text-[11px] text-emerald-700 mt-0.5">
                                Téléchargez le modèle, ouvrez-le dans Excel, saisissez vos membres et enregistrez.
                            </p>
                        </div>
                        <a
                            href="/members/template"
                            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Télécharger le modèle</span>
                        </a>
                    </div>

                    <!-- Étape 2 : Envoyer le fichier rempli -->
                    <form @submit.prevent="submitImport" class="space-y-4">
                        <div>
                            <p class="text-xs font-bold text-gray-900 mb-1">2. Envoyer le fichier complété</p>
                            <p class="text-[11px] text-gray-500 mb-2">
                                Formats acceptés : .xlsx, .xls, .csv. Les doublons et membres existants seront automatiquement détectés.
                            </p>
                            <input
                                type="file"
                                accept=".xlsx,.xls,.csv,.txt"
                                @change="handleFileChange"
                                required
                                class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#1B6B4A] file:text-white hover:file:bg-[#155A3E] cursor-pointer"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="showImportModal = false"
                                class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg cursor-pointer"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="importForm.processing"
                                class="px-5 py-2 text-xs font-semibold text-white bg-[#1B6B4A] hover:bg-[#155A3E] rounded-lg disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>{{ importForm.processing ? 'Importation en cours...' : 'Lancer l\'importation' }}</span>
                            </button>
                        </div>
                    </form>
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
import { Link, router, usePage, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    membres: {
        type: [Array, Object],
        default: () => []
    },
    filters: Object,
})

const page = usePage()

const membresPagination = computed(() => {
    return Array.isArray(props.membres) ? null : props.membres
})

const paginationLinks = computed(() => {
    return membresPagination.value?.links || []
})

const canCreateMember = computed(() => {
    if (page.props.auth?.user?.permissions) {
        return !!page.props.auth.user.permissions.creer_membre
    }
    const role = page.props.auth?.user?.role
    return role === 'administrateur' || role === 'gestionnaire'
})

const canEditMember = computed(() => {
    if (page.props.auth?.user?.permissions) {
        return !!page.props.auth.user.permissions.modifier_membre
    }
    const role = page.props.auth?.user?.role
    return role === 'administrateur' || role === 'gestionnaire'
})

const search = ref(props.filters?.search || '')
const statut = ref(props.filters?.statut || 'tous')

const displayedMembres = computed(() => {
    if (Array.isArray(props.membres)) {
        return props.membres
    }
    return props.membres?.data || []
})

function handleSearch() {
    router.get('/membres', {
        search: search.value,
        statut: statut.value
    }, { preserveState: true })
}

// Modal Détails (Demande 1)
const showDetailsModal = ref(false)
const selectedMember = ref(null)

function viewDetails(membre) {
    selectedMember.value = membre
    showDetailsModal.value = true
}

// Modal Confirmation Card (Demande 4)
const confirmData = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirmer',
    isDanger: false,
    action: null,
})

function askToggleStatut(membre) {
    const actionName = membre.statut === 'actif' ? 'désactiver' : 'réactiver'
    confirmData.value = {
        show: true,
        title: `${membre.statut === 'actif' ? 'Désactiver' : 'Réactiver'} le membre`,
        message: `Voulez-vous vraiment ${actionName} le membre ${membre.prenom} ${membre.nom} ?`,
        confirmText: membre.statut === 'actif' ? 'Désactiver' : 'Réactiver',
        isDanger: membre.statut === 'actif',
        action: () => {
            router.post(`/members/${membre.id}/toggle-statut`, {}, {
                preserveScroll: true,
            })
        }
    }
}

function handleConfirmAction() {
    if (confirmData.value.action) {
        confirmData.value.action()
    }
    confirmData.value.show = false
}

// Import Excel / CSV (Demande 10)
const showImportModal = ref(false)
const importForm = useForm({
    fichier: null,
})

function handleFileChange(e) {
    importForm.fichier = e.target.files[0]
}

function submitImport() {
    if (!importForm.fichier) return
    importForm.post('/members/import', {
        onSuccess: () => {
            showImportModal.value = false
            importForm.reset()
        }
    })
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
