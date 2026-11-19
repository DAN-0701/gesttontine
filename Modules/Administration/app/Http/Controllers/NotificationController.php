<?php

namespace Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Administration\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Retourner les notifications non lues (pour la cloche navbar)
     */
    public function index()
    {
        $notifications = Notification::orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'titre'      => $n->titre,
                'message'    => $n->message,
                'cycle_id'   => $n->cycle_id,
                'tour_id'    => $n->tour_id,
                'lue'        => $n->lue,
                'created_at' => $n->created_at ? $n->created_at->diffForHumans() : null,
            ]);

        $nonLues = Notification::where('lue', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'non_lues'      => $nonLues,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerLue(Notification $notification)
    {
        $notification->update(['lue' => true]);
        return response()->json(['success' => true]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerToutesLues()
    {
        Notification::where('lue', false)->update(['lue' => true]);
        return response()->json(['success' => true, 'message' => 'Toutes les notifications marquées comme lues.']);
    }
}
