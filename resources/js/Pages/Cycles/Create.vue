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
                    <!-- Sélecteur de Devise (Demande 6) -->
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
                        <p v-if="form.errors.date_debut" class="text-xs text-red-600 mt-1">{{ form.errors.date_debut }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Date de fin</label>
                        <input
                            v-model="form.date_fin"
                            type="date"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                        <p v-if="form.errors.date_fin" class="text-xs text-red-600 mt-1">{{ form.errors.date_fin }}</p>
                    </div>
                </div>

                <!-- Ligne 2 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Pénalité ({{ currencyLabel }})</label>
                        <input
                            v-model.number="form.montant_penalite"
                            type="number"
                            placeholder="ex: 2000"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Frais de gestion par tour ({{ currencyLabel }})</label>
                        <input
                            v-model.number="form.frais_gestion"
                            type="number"
                            placeholder="ex: 5000"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>
                </div>

                <!-- Info Box de calcul dynamique -->
                <div class="bg-[#E8F0EC] text-[#155A3E] p-4 rounded-xl text-xs space-y-1">
                    <p class="font-medium">
                        {{ cycleMembers.length }} membre(s) donnent {{ cycleMembers.length }} tour(s) et {{ cycleMembers.length * cycleMembers.length }} cotisation(s).
                    </p>
                    <p>
                        Cagnotte par tour : {{ cycleMembers.length }} &times; {{ formatMoney(form.montant_cotisation || 0) }} &minus; {{ formatMoney(form.frais_gestion || 0) }} = {{ formatMoney(calculatedPot) }} {{ currencyLabel }}.
                    </p>
                </div>
            </div>

            <!-- Membres section (2 colonnes) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <!-- Colonne gauche: Membres disponibles dans la base de données -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">Tous les membres</h3>
                        <span class="text-xs text-gray-500 font-medium">
                            {{ availableCount }} disponible(s) sur {{ allMembers.length }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1.5">Rechercher un membre</label>
                        <input
                            v-model="searchMember"
                            type="text"
                            placeholder="ex: Kouadio ou 0700"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>

                    <button
                        type="button"
                        @click="addSelectedMembers"
                        :disabled="selectedAvailableIds.length === 0"
                        class="w-full py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        Ajouter les membres sélectionnés ({{ selectedAvailableIds.length }})
                    </button>

                    <div v-if="availableCount > 0" class="flex items-center gap-2 pt-2 border-b border-gray-100 pb-2">
                        <input
                            type="checkbox"
                            id="selectAll"
                            v-model="isAllSelected"
                            @change="toggleSelectAll"
                            class="rounded border-gray-300 text-[#1B6B4A] focus:ring-[#1B6B4A]"
                        />
                        <label for="selectAll" class="text-xs font-semibold text-gray-700">Tout sélectionner</label>
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

                    <p class="text-xs text-gray-400 pt-2">
                        Seuls les membres actifs sont listés. Un membre désactivé n'apparaît jamais ici.
                    </p>
                    <Link href="/membres/create" class="inline-block text-xs font-semibold text-[#1B6B4A] hover:underline">
                        Membre introuvable ? Créer un nouveau membre
                    </Link>
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
                                    class="w-7 h-7 flex items-center justify-center rounded border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30"
                                >
                                    &uarr;
                                </button>
                                <button
                                    type="button"
                                    @click="moveDown(index)"
                                    :disabled="index === cycleMembers.length - 1"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30"
                                >
                                    &darr;
                                </button>
                                <button
                                    type="button"
                                    @click="removeMember(cm.id)"
                                    class="ml-1 px-3 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold"
                                >
                                    Retirer
                                </button>
                            </div>
                        </div>

                        <div v-if="cycleMembers.length === 0" class="py-8 text-center text-gray-400 text-xs">
                            Aucun membre sélectionné pour ce cycle. Cochez les membres à gauche et cliquez sur "Ajouter".
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center gap-4 pt-4">
                <button
                    type="button"
                    @click="saveDraft"
                    :disabled="form.processing"
                    class="px-5 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
                >
                    Enregistrer le brouillon
                </button>
                <Link
                    href="/cycles"
                    class="px-5 py-2.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors"
                >
                    Annuler
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    cycle: Object,
    membresDuCycle: {
        type: Array,
        default: () => []
    },
    membresDisponibles: {
        type: Array,
        default: () => []
    },
})

const isEditing = computed(() => !!props.cycle?.id)

// Gestion de la devise (Demande 6)
const currency = ref('FCFA')
const currencyLabel = computed(() => {
    if (currency.value === 'EUR') return '€'
    if (currency.value === 'USD') return '$'
    return 'FCFA'
})

// Formulaire : champs vides avec placeholders au lieu de valeurs par défaut codées en dur
const form = useForm({
    libelle: props.cycle?.libelle || '',
    montant_cotisation: props.cycle?.montant_cotisation || null,
    periodicite: props.cycle?.periodicite || 'mensuelle',
    date_debut: props.cycle?.date_debut || '',
    date_fin: props.cycle?.date_fin || '',
    delai_tolerance_jours: props.cycle?.delai_tolerance_jours ?? null,
    montant_penalite: props.cycle?.montant_penalite ?? null,
    frais_gestion: props.cycle?.frais_gestion ?? null,
    membres: [],
})

// Uniquement les membres réels en base de données
const allMembers = ref(props.membresDisponibles || [])
const cycleMembers = ref(props.membresDuCycle ? [...props.membresDuCycle] : [])

const searchMember = ref('')
const selectedAvailableIds = ref([])
const isAllSelected = ref(false)

const isAlreadyInCycle = (id) => {
    return cycleMembers.value.some(cm => cm.id === id)
}

const filteredAvailableMembers = computed(() => {
    let list = allMembers.value
    if (searchMember.value) {
        const q = searchMember.value.toLowerCase()
        list = list.filter(m => m.nom.toLowerCase().includes(q) || (m.telephone && m.telephone.includes(q)))
    }
    return list
})

const availableCount = computed(() => {
    return allMembers.value.filter(m => !isAlreadyInCycle(m.id)).length
})

const calculatedPot = computed(() => {
    const total = cycleMembers.value.length * (form.montant_cotisation || 0)
    return Math.max(0, total - (form.frais_gestion || 0))
})

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedAvailableIds.value = allMembers.value
            .filter(m => !isAlreadyInCycle(m.id))
            .map(m => m.id)
    } else {
        selectedAvailableIds.value = []
    }
}

function addSelectedMembers() {
    selectedAvailableIds.value.forEach(id => {
        const found = allMembers.value.find(m => m.id === id)
        if (found && !isAlreadyInCycle(id)) {
            cycleMembers.value.push({
                id: found.id,
                nom: found.nom,
                telephone: found.telephone,
                rang: cycleMembers.value.length + 1
            })
        }
    })
    selectedAvailableIds.value = []
    isAllSelected.value = false
}

function removeMember(id) {
    cycleMembers.value = cycleMembers.value.filter(m => m.id !== id)
}

function moveUp(index) {
    if (index > 0) {
        const temp = cycleMembers.value[index]
        cycleMembers.value[index] = cycleMembers.value[index - 1]
        cycleMembers.value[index - 1] = temp
    }
}

function moveDown(index) {
    if (index < cycleMembers.value.length - 1) {
        const temp = cycleMembers.value[index]
        cycleMembers.value[index] = cycleMembers.value[index + 1]
        cycleMembers.value[index + 1] = temp
    }
}

function saveDraft() {
    // Transmettre la liste ordonnée des IDs des membres sélectionnés
    form.membres = cycleMembers.value.map(cm => cm.id)

    if (isEditing.value) {
        form.put(`/cycles/${props.cycle.id}`)
    } else {
        form.post('/cycles')
    }
}

function formatMoney(amount) {
    if (amount === undefined || amount === null) return '0'
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}
</script>
