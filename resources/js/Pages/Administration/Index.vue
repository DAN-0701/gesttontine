<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Administration des Permissions</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Attribution ou révocation des permissions granulaires pour les gestionnaires et trésoriers.
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Espace Super Administrateur
                </span>
            </div>

            <!-- Matrice Utilisateurs & Permissions -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-gray-500 border-b border-gray-100 pb-3">
                                <th class="pb-3 pr-4 font-semibold text-sm">Utilisateur</th>
                                <th class="pb-3 pr-4 font-semibold text-sm">Rôle de base</th>
                                <th v-for="p in permissions" :key="p.id" class="pb-3 px-2 text-center min-w-[120px]">
                                    <div class="font-bold text-gray-900 capitalize">{{ p.nom.replace('_', ' ') }}</div>
                                    <div class="text-[10px] text-gray-400 font-normal">{{ p.description }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 pr-4">
                                    <p class="font-bold text-gray-900 text-sm">{{ u.name }}</p>
                                    <p class="text-gray-400 text-[11px]">{{ u.email }}</p>
                                </td>
                                <td class="py-4 pr-4">
                                    <span
                                        :class="[
                                            'inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize',
                                            u.role === 'gestionnaire' ? 'bg-[#155A3E] text-white' : 'bg-blue-100 text-blue-700'
                                        ]"
                                    >
                                        {{ u.role }}
                                    </span>
                                </td>
                                <td v-for="p in permissions" :key="p.id" class="py-4 px-2 text-center">
                                    <div class="inline-flex flex-col items-center gap-1">
                                        <!-- Statut de la permission -->
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="askToggle(u, p, true)"
                                                :class="[
                                                    'px-2 py-0.5 rounded text-[11px] font-bold transition-all cursor-pointer',
                                                    isGranted(u, p)
                                                        ? 'bg-emerald-600 text-white shadow-2xs'
                                                        : 'bg-gray-100 text-gray-400 hover:bg-gray-200'
                                                ]"
                                                title="Accorder explicitement"
                                            >
                                                Oui
                                            </button>
                                            <button
                                                type="button"
                                                @click="askToggle(u, p, false)"
                                                :class="[
                                                    'px-2 py-0.5 rounded text-[11px] font-bold transition-all cursor-pointer',
                                                    isDenied(u, p)
                                                        ? 'bg-red-600 text-white shadow-2xs'
                                                        : 'bg-gray-100 text-gray-400 hover:bg-gray-200'
                                                ]"
                                                title="Retirer explicitement"
                                            >
                                                Non
                                            </button>
                                        </div>

                                        <!-- Indicateur d'origine -->
                                        <span class="text-[9px] text-gray-400">
                                            {{ getPermissionOriginText(u, p) }}
                                        </span>

                                        <button
                                            v-if="hasCustomPermission(u, p)"
                                            type="button"
                                            @click="askReset(u, p)"
                                            class="text-[9px] text-gray-400 hover:text-gray-700 underline cursor-pointer"
                                            title="Rétablir les droits par défaut du rôle"
                                        >
                                            Rétablir par défaut
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users || users.length === 0">
                                <td :colspan="2 + permissions.length" class="py-8 text-center text-gray-400">
                                    Aucun gestionnaire ou trésorier trouvé.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Carte explicative des règles métier -->
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-xs text-gray-600 space-y-2">
                <p class="font-bold text-gray-800 text-sm">Fonctionnement des permissions personnalisées :</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Priorité absolue :</strong> Toute permission personnalisée définie dans <code class="font-mono bg-white px-1 py-0.5 rounded border border-gray-200">user_permissions</code> écrase le rôle de base de l'utilisateur.</li>
                    <li><strong>Bouton « Oui » (autorise = 1) :</strong> Accorde explicitement l'action (ex: un trésorier autorisé à créer un membre).</li>
                    <li><strong>Bouton « Non » (autorise = 0) :</strong> Interdit explicitement l'action (ex: un gestionnaire privé du droit de créer des cycles).</li>
                    <li><strong>« Rétablir par défaut » :</strong> Supprime l'entrée personnalisée pour appliquer les droits d'origine du rôle.</li>
                </ul>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- Modal de confirmation (Demande 2)                        -->
        <!-- ======================================================= -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div
                    v-if="confirmModal.show"
                    class="fixed inset-0 z-[200] flex items-center justify-center p-4"
                    @click.self="confirmModal.show = false"
                >
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

                    <!-- Card -->
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4 z-10">
                        <!-- Icône -->
                        <div class="flex items-center justify-center">
                            <div :class="[
                                'w-14 h-14 rounded-full flex items-center justify-center',
                                confirmModal.autorise === true
                                    ? 'bg-emerald-50'
                                    : confirmModal.autorise === false
                                    ? 'bg-red-50'
                                    : 'bg-amber-50'
                            ]">
                                <svg v-if="confirmModal.autorise === true" class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else-if="confirmModal.autorise === false" class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <svg v-else class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                        </div>

                        <!-- Titre -->
                        <div class="text-center">
                            <h3 class="text-base font-bold text-gray-900">{{ confirmModal.titre }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ confirmModal.message }}</p>
                        </div>

                        <!-- Boutons -->
                        <div class="flex gap-3 pt-2">
                            <button
                                type="button"
                                @click="confirmModal.show = false"
                                class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors cursor-pointer"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                @click="confirmAction"
                                :class="[
                                    'flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors cursor-pointer',
                                    confirmModal.autorise === true
                                        ? 'bg-emerald-600 hover:bg-emerald-700'
                                        : confirmModal.autorise === false
                                        ? 'bg-red-600 hover:bg-red-700'
                                        : 'bg-amber-600 hover:bg-amber-700'
                                ]"
                            >
                                Confirmer
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    permissions: {
        type: Array,
        default: () => []
    }
})

// Modal de confirmation
const confirmModal = ref({
    show: false,
    titre: '',
    message: '',
    autorise: null,  // true = accorder, false = refuser, null = réinitialiser
    callback: null,
})

function askToggle(user, permission, autorise) {
    const action = autorise ? 'accorder' : 'retirer'
    const permLabel = permission.nom.replace(/_/g, ' ')
    const utilisateur = user.name

    confirmModal.value = {
        show: true,
        titre: autorise ? `Accorder la permission` : `Retirer la permission`,
        message: `Voulez-vous vraiment ${action} la permission « ${permLabel} » à ${utilisateur} ? Cette action modifie immédiatement ses droits d'accès.`,
        autorise: autorise,
        callback: () => togglePermission(user, permission, autorise),
    }
}

function askReset(user, permission) {
    const permLabel = permission.nom.replace(/_/g, ' ')
    confirmModal.value = {
        show: true,
        titre: 'Rétablir les droits par défaut',
        message: `Voulez-vous rétablir la permission « ${permLabel} » de ${user.name} selon les droits par défaut de son rôle (${user.role}) ?`,
        autorise: null,
        callback: () => resetPermission(user, permission),
    }
}

function confirmAction() {
    if (confirmModal.value.callback) {
        confirmModal.value.callback()
    }
    confirmModal.value.show = false
}

function getCustomPermission(user, permission) {
    if (!user.permissions) return null
    return user.permissions.find(p => p.permission_id === permission.id || p.permission_nom === permission.nom)
}

function hasCustomPermission(user, permission) {
    return !!getCustomPermission(user, permission)
}

function isGranted(user, permission) {
    const custom = getCustomPermission(user, permission)
    if (custom) return custom.autorise === true

    // Par défaut selon le rôle
    if (user.role === 'gestionnaire') {
        return ['creer_membre', 'modifier_membre', 'supprimer_membre', 'creer_cycle', 'demarrer_cycle', 'cloturer_cycle', 'cloturer_tour', 'annuler_penalite', 'annuler_paiement'].includes(permission.nom)
    }
    if (user.role === 'tresorier') {
        return ['enregistrer_paiement', 'corriger_paiement', 'payer_penalite'].includes(permission.nom)
    }
    return false
}

function isDenied(user, permission) {
    const custom = getCustomPermission(user, permission)
    if (custom) return custom.autorise === false

    return !isGranted(user, permission)
}

function getPermissionOriginText(user, permission) {
    const custom = getCustomPermission(user, permission)
    if (custom) {
        return custom.autorise ? 'Personnalisé (Accordé)' : 'Personnalisé (Refusé)'
    }
    return 'Défaut du rôle'
}

function togglePermission(user, permission, autorise) {
    router.post('/administration/permissions', {
        user_id: user.id,
        permission_id: permission.id,
        autorise: autorise ? 1 : 0,
    }, {
        preserveScroll: true,
    })
}

function resetPermission(user, permission) {
    router.post('/administration/permissions/reset', {
        user_id: user.id,
        permission_id: permission.id,
    }, {
        preserveScroll: true,
    })
}
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
