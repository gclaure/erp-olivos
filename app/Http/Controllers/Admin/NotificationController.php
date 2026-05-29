<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        $query = ($user->is_super_admin || $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador'])) 
            ? DatabaseNotification::query()
            : $user->notifications();

        $query->select(['id', 'data', 'created_at', 'read_at', 'notifiable_id', 'notifiable_type']);

        $filter = $request->input('filter', 'all');

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'filter' => $filter
            ]
        ]);
    }

    public function markAsRead(string $id): RedirectResponse
    {
        $user = Auth::user();
        
        $query = ($user->is_super_admin || $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador'])) 
            ? DatabaseNotification::query()
            : $user->notifications();

        $notification = $query->findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $user = Auth::user();
        
        $query = ($user->is_super_admin || $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador'])) 
            ? DatabaseNotification::query()
            : $user->unreadNotifications();

        $query->update(['read_at' => now()]);

        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $user = Auth::user();
        
        $query = ($user->is_super_admin || $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador'])) 
            ? DatabaseNotification::query()
            : $user->notifications();

        $notification = $query->findOrFail($id);
        $notification->delete();

        return back()->with('success', 'Notificación eliminada.');
    }

    public function destroyAll(): RedirectResponse
    {
        $user = Auth::user();
        
        $query = ($user->is_super_admin || $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador'])) 
            ? DatabaseNotification::query()
            : $user->notifications();

        $query->delete();

        return back()->with('success', 'Todas las notificaciones han sido eliminadas.');
    }
}
