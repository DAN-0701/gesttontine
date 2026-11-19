<?php

namespace Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Administration\Models\Permission;
use Modules\Administration\Models\UserPermission;

class AdministrationController extends Controller
{
    /**
     * Vérifier que l'utilisateur connecté est un super admin
     */
    private function checkAdmin()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'administrateur') {
            abort(403, 'Accès réservé au super administrateur.');
        }
    }

    /**
     * Affichage de la matrice de gestion des permissions
     */
    public function index()
    {
        $this->checkAdmin();

        $users = Users::whereIn('role', ['gestionnaire', 'tresorier'])
            ->with(['userPermissions.permission'])
            ->get()
            ->map(function ($u) {
                return [
                    'id'          => $u->id,
                    'name'        => $u->name,
                    'email'       => $u->email,
                    'role'        => $u->role,
                    'permissions' => $u->userPermissions->map(fn($up) => [
                        'permission_id'   => $up->permission_id,
                        'permission_nom'  => $up->permission ? $up->permission->nom : null,
                        'autorise'        => (bool) $up->autorise,
                    ]),
                ];
            });

        $permissions = Permission::all();

        return Inertia::render('Administration/Index', [
            'users'       => $users,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Attribuer ou mettre à jour une permission personnalisée pour un utilisateur
     * autorise = true : accorde explicitement
     * autorise = false : retire explicitement
     */
    public function setPermission(Request $request)
    {
        $this->checkAdmin();

        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'permission_id' => 'required|exists:permissions,id',
            'autorise'      => 'required|boolean',
        ]);

        UserPermission::updateOrCreate(
            [
                'user_id'       => $data['user_id'],
                'permission_id' => $data['permission_id'],
            ],
            [
                'autorise'      => $data['autorise'],
            ]
        );

        return redirect()->back()->with('success', 'Permission mise à jour avec succès.');
    }

    /**
     * Réinitialiser une permission pour qu'elle reprenne le comportement par défaut du rôle
     */
    public function resetPermission(Request $request)
    {
        $this->checkAdmin();

        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'permission_id' => 'required|exists:permissions,id',
        ]);

        UserPermission::where('user_id', $data['user_id'])
            ->where('permission_id', $data['permission_id'])
            ->delete();

        return redirect()->back()->with('success', 'Permission réinitialisée aux droits du rôle.');
    }
}
