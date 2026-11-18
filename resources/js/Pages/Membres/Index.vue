<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Membres</h1>
                    <p class="text-sm text-gray-500 mt-1">Les personnes inscrites dans la tontine.</p>
                </div>
                <!-- Masqué pour le trésorier -->
                <Link
                    v-if="isGestionnaire"
                    href="/membres/create"
                    class="inline-flex items-center px-4 py-2 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
                >
                    Nouveau membre
                </Link>
            </div>

            <!-- Filters form -->
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">Rechercher par nom ou téléphone</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="ex: 0700 ou Kouadio"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                    />
                </div>
                <div class="w-full md:w-44">
                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">Statut</label>
                    <select
                        v-model="statut"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                    >
                        <option value="tous">Tous</option>
                        <option value="actif">Actif</option>
                        <option value="desactive">Désactivé</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="handleSearch"
                        class="w-full md:w-auto px-5 py-2.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-800 text-sm font-semibold rounded-lg transition-colors"
                    >
                        Rechercher
                    </button>
                </div>
            </div>

            <!-- Members Table Card -->
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
                                <th v-if="isGestionnaire" class="pb-3 font-semibold text-right">Action</th>
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
                                <td v-if="isGestionnaire" class="py-3.5 text-right">
                                    <Link
                                        :href="`/members/${membre.id}/edit`"
                                        class="text-[#1B6B4A] hover:underline font-medium text-sm"
                                    >
                                        Modifier
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="displayedMembres.length === 0">
                                <td :colspan="isGestionnaire ? 6 : 5" class="py-8 text-center text-gray-400">
                                    Aucun membre enregistré en base de données.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Note -->
            <p class="text-xs text-gray-500">
                Un membre désactivé n'apparaît plus dans la sélection des membres d'un nouveau cycle.
            </p>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    membres: {
        type: Array,
        default: () => []
    },
    filters: Object,
})

const page = usePage()

const isGestionnaire = computed(() => {
    const role = page.props.auth?.user?.role
    // Par défaut si non spécifié, considérer gestionnaire, sinon si trésorier -> false
    return role !== 'tresorier'
})

const search = ref(props.filters?.search || '')
const statut = ref(props.filters?.statut || 'tous')

// Uniquement les membres réels de la base de données
const displayedMembres = computed(() => {
    let list = props.membres || []

    if (search.value) {
        const q = search.value.toLowerCase()
        list = list.filter(m =>
            (m.nom && m.nom.toLowerCase().includes(q)) ||
            (m.prenom && m.prenom.toLowerCase().includes(q)) ||
            (m.telephone && m.telephone.toLowerCase().includes(q))
        )
    }

    if (statut.value && statut.value !== 'tous') {
        list = list.filter(m => m.statut === statut.value)
    }

    return list
})

function handleSearch() {
    router.get('/membres', {
        search: search.value,
        statut: statut.value
    }, { preserveState: true })
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
