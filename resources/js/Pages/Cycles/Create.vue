<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <Link href="/cycles" class="text-sm font-semibold text-[#1B6B4A] hover:underline inline-flex items-center gap-1 mb-2">
                    &larr; Retour aux cycles
                </Link>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ isEditing ? (form.libelle || 'Modifier le cycle') : 'Nouveau cycle' }}
                    </h1>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">
                        Brouillon
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Définissez les paramètres du cycle, puis ajoutez les membres. Tout reste modifiable tant que le cycle n'est pas démarré.
                </p>
            </div>

            <!-- Paramètres du cycle -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-900">Paramètres du cycle</h2>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-gray-700">Devise :</label>
                        <select
                            v-model="currency"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        >
                            <option value="FCFA">FCFA</option>
                            <option value="EUR">Euro (€)</option>
                            <option value="USD">Dollar ($)</option>
                        </select>
                    </div>
                </div>

                <!-- Ligne 1 -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Nom du cycle</label>
                        <input
                            v-model="form.libelle"
                            type="text"
                            placeholder="ex: Tontine Famille 2026"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                        <p v-if="form.errors.libelle" class="text-xs text-red-600 mt-1">{{ form.errors.libelle }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Cotisation ({{ currencyLabel }})</label>
                        <input
                            v-model.number="form.montant_cotisation"
                            type="number"
                            placeholder="ex: 20000"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                        <p v-if="form.errors.montant_cotisation" class="text-xs text-red-600 mt-1">{{ form.errors.montant_cotisation }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Périodicité</label>
                        <select
                            v-model="form.periodicite"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        >
                            <option value="mensuelle">Mensuelle</option>
                            <option value="trimestrielle">Trimestrielle</option>
                            <option value="semestrielle">Semestrielle</option>
                            <option value="annuelle">Annuelle</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Date de début</label>
                        <input
                            v-model="form.date_debut"
                            type="date"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Date de fin (facultative)</label>
                        <input
                            v-model="form.date_fin"
                            type="date"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>
                </div>

                <!-- Ligne 2 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Tolérance (jours)</label>
                        <input
                            v-model.number="form.delai_tolerance_jours"
                            type="number"
                            placeholder="ex: 2"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Pénalité par retard ({{ currencyLabel }})</label>
                        <input
                            v-model.number="form.montant_penalite"
                            type="number"
                            placeholder="ex: 1000"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Frais de gestion ({{ currencyLabel }})</label>
                        <input
                            v-model.number="form.frais_gestion"
                            type="number"
                            placeholder="ex: 5000"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>
                </div>
            </div>

            <!-- Sélection et Organisation des Membres -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <!-- Colonne gauche: Membres disponibles -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">Membres disponibles</h3>
                        <span class="text-xs text-gray-500 font-medium">{{ filteredAvailableMembers.length }} disponible(s)</span>
                    </div>

                    <!-- Recherche membre -->
                    <input
                        v-model="memberSearch"
                        type="text"
                        placeholder="Rechercher par nom ou numéro..."
                        class="w-full px-3.5 py-2 rounded-lg border border-gray-300 bg-white text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                    />

                    <!-- Tout sélectionner & bouton ajouter -->
                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-2">
                            <input
                                id="selectAll"
                                type="checkbox"
                                v-model="isAllSelected"
                                @change="toggleSelectAll"
                                class="rounded border-gray-300 text-[#1B6B4A] focus:ring-[#1B6B4A]"
                            />
                            <label for="selectAll" class="text-xs font-semibold text-gray-700">Tout sélectionner</label>
                        </div>
                        <button
                            type="button"
                            @click="addSelectedMembers"
                            :disabled="selectedAvailableIds.length === 0"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-xs font-semibold rounded-lg disabled:opacity-40 transition-colors cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Ajouter ({{ selectedAvailableIds.length }})</span>
                        </button>
                    </div>

                    <!-- Liste scrollable des membres réels en base -->
                    <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                        <div
                            v-for="m in filteredAvailableMembers"
                            :key="m.id"
                            :class="[
                                'flex items-center justify-between p-3 rounded-xl border transition-colors',
                                isAlreadyInCycle(m.id)
                                    ? 'bg-gray-50 border-gray-100 text-gray-400'
                                    : 'bg-white border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    v-if="!isAlreadyInCycle(m.id)"
                                    type="checkbox"
                                    :value="m.id"
                                    v-model="selectedAvailableIds"
                                    class="rounded border-gray-300 text-[#1B6B4A] focus:ring-[#1B6B4A]"
                                />
                                <div>
                                    <p class="text-sm font-semibold" :class="isAlreadyInCycle(m.id) ? 'text-gray-500' : 'text-gray-900'">
                                        {{ m.nom }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ m.telephone }}</p>
                                </div>
                            </div>
                            <span
                                v-if="isAlreadyInCycle(m.id)"
                                class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full font-medium"
                            >
                                Dans le cycle
                            </span>
                        </div>

                        <div v-if="filteredAvailableMembers.length === 0" class="py-6 text-center text-gray-400 text-xs">
                            Aucun membre trouvé en base de données.
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
                        <p class="text-xs text-gray-400">
                            Seuls les membres actifs sont listés.
                        </p>
                        <!-- Bouton Modal Création Membre en direct (Demande 9: sans quitter ni reset le cycle) -->
                        <button
                            type="button"
                            @click="showCreateMemberModal = true"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B6B4A] hover:underline cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <span>Créer un membre ici</span>
                        </button>
                    </div>
                </div>

                <!-- Colonne droite: Membres inclus dans le cycle -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">Membres du cycle</h3>
                        <span class="text-xs text-gray-500 font-medium">{{ cycleMembers.length }} membre(s)</span>
                    </div>

                    <p class="text-xs text-gray-500 leading-relaxed">
                        Le rang 1 reçoit au tour 1. Les rangs se renumérotent seuls : deux membres ne peuvent pas avoir le même rang.
                    </p>

                    <!-- Liste ordonnée des membres du cycle -->
                    <div class="space-y-3">
                        <div
                            v-for="(cm, index) in cycleMembers"
                            :key="cm.id"
                            class="flex items-center justify-between p-3 rounded-xl border border-gray-200 bg-white shadow-2xs"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-[#1B6B4A] text-white flex items-center justify-center text-xs font-bold">
                                    {{ index + 1 }}
                                </span>
                                <span class="text-sm font-semibold text-gray-900">{{ cm.nom }}</span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="moveUp(index)"
                                    :disabled="index === 0"
                                    class="p-1 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded disabled:opacity-20 cursor-pointer"
                                    title="Monter le rang"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="moveDown(index)"
                                    :disabled="index === cycleMembers.length - 1"
                                    class="p-1 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded disabled:opacity-20 cursor-pointer"
                                    title="Descendre le rang"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <!-- Retirer du cycle avec modal card (Demande 4) -->
                                <button
                                    type="button"
                                    @click="askRemoveMember(cm, index)"
                                    class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded ml-1 cursor-pointer"
                                    title="Retirer du cycle"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="cycleMembers.length === 0" class="py-10 text-center text-gray-400 text-xs border border-dashed border-gray-200 rounded-xl">
                            Aucun membre dans le cycle pour l'instant. Cochez des membres à gauche et cliquez sur « Ajouter ».
                        </div>
                    </div>

                    <!-- Encart vert résumé -->
                    <div v-if="cycleMembers.length > 0" class="bg-[#1B6B4A] text-white p-4 rounded-xl space-y-1 mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-100">Cagnotte par tour</p>
                        <p class="text-xl font-bold">
                            {{ formatMoney(totalCagnotte) }} {{ currencyLabel }}
                        </p>
                        <p class="text-xs text-emerald-100">
                            {{ cycleMembers.length }} cotisations de {{ formatMoney(form.montant_cotisation || 0) }} {{ currencyLabel }}
                            <template v-if="form.frais_gestion > 0">
                                · moins {{ formatMoney(form.frais_gestion) }} {{ currencyLabel }} de frais
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <Link
                    href="/cycles"
                    class="px-5 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors"
                >
                    Annuler
                </Link>
                <button
                    type="button"
                    @click="submit"
                    :disabled="form.processing || cycleMembers.length === 0"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ isEditing ? 'Enregistrer les modifications' : 'Enregistrer le cycle' }}</span>
                </button>
            </div>

            <!-- Modal Création Membre en direct (Demande 9) -->
            <div
                v-if="showCreateMemberModal"
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 backdrop-blur-xs animate-in fade-in duration-150"
                @click.self="showCreateMemberModal = false"
            >
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="text-base font-bold text-gray-900">Créer un nouveau membre</h3>
                        <button
                            @click="showCreateMemberModal = false"
                            class="text-gray-400 hover:text-gray-600 font-bold p-1 text-sm cursor-pointer"
                        >
                            &times;
                        </button>
                    </div>

                    <form @submit.prevent="submitCreateMember" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-800 mb-1">Nom *</label>
                                <input
                                    v-model="newMemberForm.nom"
                                    type="text"
                                    required
                                    placeholder="ex: Kouassi"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs text-gray-900 focus:ring-2 focus:ring-[#1B6B4A]"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-800 mb-1">Prénom *</label>
                                <input
                                    v-model="newMemberForm.prenom"
                                    type="text"
                                    required
                                    placeholder="ex: Jean"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs text-gray-900 focus:ring-2 focus:ring-[#1B6B4A]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-800 mb-1">Téléphone *</label>
                                <input
                                    v-model="newMemberForm.telephone"
                                    type="text"
                                    required
                                    placeholder="ex: 0700000000"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs text-gray-900 focus:ring-2 focus:ring-[#1B6B4A]"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-800 mb-1">Email</label>
                                <input
                                    v-model="newMemberForm.email"
                                    type="email"
                                    placeholder="ex: jean@mail.ci"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs text-gray-900 focus:ring-2 focus:ring-[#1B6B4A]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-800 mb-1">Adresse *</label>
                            <input
                                v-model="newMemberForm.adresse"
                                type="text"
                                required
                                placeholder="ex: Abidjan, Cocody"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs text-gray-900 focus:ring-2 focus:ring-[#1B6B4A]"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="showCreateMemberModal = false"
                                class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg cursor-pointer"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="newMemberLoading"
                                class="px-4 py-2 text-xs font-semibold text-white bg-[#1B6B4A] hover:bg-[#155A3E] rounded-lg disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ newMemberLoading ? 'Enregistrement...' : 'Enregistrer le membre' }}</span>
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
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    cycle: Object,
    membresDisponibles: {
        type: Array,
        default: () => []
    },
    membresDuCycle: {
        type: Array,
        default: () => []
    },
})

const isEditing = computed(() => !!props.cycle?.id)

// Devise
const currency = ref('FCFA')
const currencyLabel = computed(() => {
    if (currency.value === 'EUR') return '€'
    if (currency.value === 'USD') return '$'
    return 'FCFA'
})

// Formulaire Cycle
const form = useForm({
    libelle: props.cycle?.libelle || '',
    montant_cotisation: props.cycle?.montant_cotisation || null,
    periodicite: props.cycle?.periodicite || 'mensuelle',
    date_debut: props.cycle?.date_debut || '',
    date_fin: props.cycle?.date_fin || '',
    delai_tolerance_jours: props.cycle?.delai_tolerance_jours ?? 2,
    montant_penalite: props.cycle?.montant_penalite ?? 1000,
    frais_gestion: props.cycle?.frais_gestion ?? 0,
    membres: [],
})

// Liste locale des membres disponibles
const availableMembersList = ref([...props.membresDisponibles])

// Membres sélectionnés pour le cycle
const cycleMembers = ref([...props.membresDuCycle])
const selectedAvailableIds = ref([])
const memberSearch = ref('')
const isAllSelected = ref(false)

const filteredAvailableMembers = computed(() => {
    if (!memberSearch.value) return availableMembersList.value
    const q = memberSearch.value.toLowerCase()
    return availableMembersList.value.filter(m =>
        (m.nom && m.nom.toLowerCase().includes(q)) ||
        (m.telephone && m.telephone.toLowerCase().includes(q))
    )
})

function isAlreadyInCycle(id) {
    return cycleMembers.value.some(m => m.id === id)
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedAvailableIds.value = filteredAvailableMembers.value
            .filter(m => !isAlreadyInCycle(m.id))
            .map(m => m.id)
    } else {
        selectedAvailableIds.value = []
    }
}

function addSelectedMembers() {
    selectedAvailableIds.value.forEach(id => {
        const found = availableMembersList.value.find(m => m.id === id)
        if (found && !isAlreadyInCycle(id)) {
            cycleMembers.value.push({ ...found })
        }
    })
    selectedAvailableIds.value = []
    isAllSelected.value = false
}

// Confirmation Card pour retirer un membre (Demande 4)
const confirmData = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirmer',
    isDanger: false,
    action: null,
})

function askRemoveMember(member, index) {
    confirmData.value = {
        show: true,
        title: 'Retirer le membre du cycle',
        message: `Voulez-vous vraiment retirer ${member.nom} de ce cycle ?`,
        confirmText: 'Retirer',
        isDanger: true,
        action: () => {
            cycleMembers.value.splice(index, 1)
        }
    }
}

function handleConfirmAction() {
    if (confirmData.value.action) {
        confirmData.value.action()
    }
    confirmData.value.show = false
}

function moveUp(index) {
    if (index <= 0) return
    const temp = cycleMembers.value[index]
    cycleMembers.value[index] = cycleMembers.value[index - 1]
    cycleMembers.value[index - 1] = temp
}

function moveDown(index) {
    if (index >= cycleMembers.value.length - 1) return
    const temp = cycleMembers.value[index]
    cycleMembers.value[index] = cycleMembers.value[index + 1]
    cycleMembers.value[index + 1] = temp
}

const totalCagnotte = computed(() => {
    const total = (cycleMembers.value.length * (form.montant_cotisation || 0)) - (form.frais_gestion || 0)
    return Math.max(0, total)
})

function submit() {
    form.membres = cycleMembers.value.map(m => m.id)
    if (isEditing.value) {
        form.put(`/cycles/${props.cycle.id}`)
    } else {
        form.post('/cycles')
    }
}

// Modal Création Membre en direct (Demande 9)
const showCreateMemberModal = ref(false)
const newMemberLoading = ref(false)
const newMemberForm = ref({
    nom: '',
    prenom: '',
    telephone: '',
    email: '',
    adresse: '',
    date_adhesion: new Date().toISOString().split('T')[0],
    statut: 'actif',
})

async function submitCreateMember() {
    newMemberLoading.value = true
    try {
        const res = await fetch('/members', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(newMemberForm.value),
        })

        const data = await res.json()
        if (data && data.member) {
            const newMember = {
                id: data.member.id,
                nom: `${data.member.prenom} ${data.member.nom}`,
                telephone: data.member.telephone,
                dans_cycle: false,
            }
            availableMembersList.value.unshift(newMember)
            cycleMembers.value.push(newMember)
            showCreateMemberModal.value = false
            newMemberForm.value = {
                nom: '',
                prenom: '',
                telephone: '',
                email: '',
                adresse: '',
                date_adhesion: new Date().toISOString().split('T')[0],
                statut: 'actif',
            }
        }
    } catch (e) {
        alert("Erreur lors de la création du membre.")
    } finally {
        newMemberLoading.value = false
    }
}

function formatMoney(amount) {
    if (amount === undefined || amount === null) return '0'
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}
</script>
