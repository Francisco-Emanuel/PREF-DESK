<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    /**
     * Exibe a lista completa de notificações do usuário logado.
     */
    public function index(): View
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(10);
        
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Retorna a contagem de notificações não lidas do usuário logado via API.
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = Auth::user();
        $unreadNotifications = $user->unreadNotifications()->count();
        return response()->json($unreadNotifications);
    }
    
    /**
     * Marca uma notificação específica como lida e redireciona.
     */
    public function markAsRead(Request $request, $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
            
            return redirect($notification->data['url'] ?? route('notifications.index'));
        }

        return back()->with('error', 'Notificação não encontrada.');
    }
    
}