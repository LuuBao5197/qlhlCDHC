<?php

namespace App\Notifications;

use App\Enums\InternalNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InternalNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly InternalNotificationType $type,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $url,
        public readonly string $dedupeKey,
        public readonly array $meta = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge($this->meta, [
            'type' => $this->type->value,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'dedupe_key' => $this->dedupeKey,
            'icon' => $this->type->icon(),
            'icon_color_class' => $this->type->iconColorClass(),
            'badge_class' => $this->type->badgeClass(),
            'type_label' => $this->type->label(),
        ]);
    }
}
