<?php

namespace App\Http\Controllers;

use App\Support\NotificationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user, 403);

        $notifications = $user->notifications()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $notifications->setCollection(
            $notifications->getCollection()->map(fn (DatabaseNotification $notification): array => NotificationPresenter::present($notification))
        );

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function show(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        if ($record->read_at === null) {
            $record->markAsRead();
        }

        $targetUrl = (string) data_get(
            $record->data,
            'url',
            Route::has('notifications.index') ? route('notifications.index') : url('/')
        );

        return redirect()->to($targetUrl);
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Da danh dau tat ca thong bao la da doc.');
    }
}
