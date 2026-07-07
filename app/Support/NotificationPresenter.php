<?php

namespace App\Support;

use App\Enums\InternalNotificationType;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NotificationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $type = InternalNotificationType::tryFrom((string) ($data['type'] ?? ''));

        return [
            'id' => $notification->id,
            'type' => (string) ($data['type'] ?? ''),
            'type_label' => (string) ($data['type_label'] ?? $type?->label() ?? 'Thong bao'),
            'title' => (string) ($data['title'] ?? 'Thong bao moi'),
            'message' => (string) ($data['message'] ?? ''),
            'excerpt' => Str::limit((string) ($data['message'] ?? ''), 120),
            'url' => (string) ($data['url'] ?? (Route::has('notifications.index') ? route('notifications.index') : '#')),
            'icon' => (string) ($data['icon'] ?? $type?->icon() ?? 'mdi-bell-outline'),
            'icon_color_class' => (string) ($data['icon_color_class'] ?? $type?->iconColorClass() ?? 'text-info'),
            'badge_class' => (string) ($data['badge_class'] ?? $type?->badgeClass() ?? 'badge-info'),
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at,
            'created_at_human' => optional($notification->created_at)->diffForHumans(),
            'read_at' => $notification->read_at,
        ];
    }
}
