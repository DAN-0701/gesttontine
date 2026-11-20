<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Cotisation\Models\Paiements;
use Modules\Cotisation\Models\Penalites;
use Modules\Cycle\Models\Cycle;
use Modules\Administration\Models\Permission;
use Modules\Administration\Models\UserPermission;

class Users extends Authenticatable
{
    use HasFactory, Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'force_password_change'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function cycles()
    {
        return $this->hasMany(Cycle::class, 'createur_id');
    }

    public function paiements()
    {
        return $this->hasMany(Paiements::class, 'enregistre_par');
    }

    public function penalites()
    {
        return $this->hasMany(Penalites::class, 'traite_par');
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class, 'user_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
                    ->withPivot('autorise')
                    ->withTimestamps();
    }

    /**
     * Vérifier si l'utilisateur possède une permission
     *
     * Règle de priorité :
     * 1. Si rôle 'administrateur' (super administrateur) => toujours true
     * 2. Si une entrée existe dans 'user_permissions' pour cette permission => la valeur 'autorise' (true ou false) l'emporte
     * 3. Sinon, appliquer les droits par défaut du rôle de base
     */
    public function hasPermission(string $permissionNom): bool
    {
        // 1. Super Administrateur : tous les pouvoirs
        if ($this->role === 'administrateur') {
            return true;
        }

        // 2. Recherche d'une permission personnalisée explicite
        $customPermission = $this->userPermissions()
            ->whereHas('permission', fn($q) => $q->where('nom', $permissionNom))
            ->first();

        if ($customPermission !== null) {
            return (bool) $customPermission->autorise;
        }

        // 3. Droits par défaut basés sur le rôle
        return match ($this->role) {
            'gestionnaire' => match ($permissionNom) {
                'creer_membre', 'modifier_membre', 'supprimer_membre',
                'creer_cycle', 'demarrer_cycle', 'cloturer_cycle', 'cloturer_tour',
                'annuler_penalite', 'annuler_paiement',
                'creer_utilisateur', 'changer_beneficiaire',
                'consulter' => true,
                default => false,
            },
            'tresorier' => match ($permissionNom) {
                'enregistrer_paiement', 'corriger_paiement',
                'payer_penalite',
                'consulter' => true,
                default => false,
            },
            default => false,
        };
    }

    /**
     * Retourne la liste de toutes les permissions avec leur valeur booléenne pour l'utilisateur
     */
    public function getPermissionsArray(): array
    {
        $perms = [
            'creer_membre', 'modifier_membre', 'supprimer_membre',
            'creer_cycle', 'demarrer_cycle', 'cloturer_cycle', 'cloturer_tour',
            'enregistrer_paiement', 'corriger_paiement', 'annuler_paiement',
            'payer_penalite', 'annuler_penalite',
            'creer_utilisateur', 'changer_beneficiaire',
            'consulter',
        ];
        $result = [];
        foreach ($perms as $p) {
            $result[$p] = $this->hasPermission($p);
        }
        return $result;
    }
}
