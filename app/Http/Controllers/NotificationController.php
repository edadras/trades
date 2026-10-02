<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20)->through(fn ($n) => [
            'id' => $n->id,
            'event' => $n->data['event'] ?? null,
            'params' => $n->data['params'] ?? [],
            'url' => $n->data['url'] ?? null,
            'read' => (bool) $n->read_at,
            'created_at' => $n->created_at->toIso8601String(),
        ]);

        return Inertia::render('Notifications/Index', ['notifications' => $notifications]);
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        return $url && str_starts_with($url, config('app.url')) ? redirect()->to($url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
