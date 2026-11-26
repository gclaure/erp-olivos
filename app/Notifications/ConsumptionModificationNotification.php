<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConsumptionModificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $consumptionRequestId,
        private readonly string $modificationId,
        private readonly string $title,
        private readonly string $message,
        private readonly string $eventType,
        private readonly string $actionUrl
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'consumption_request_id' => $this->consumptionRequestId,
            'modification_id' => $this->modificationId,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->eventType,
            'url' => $this->actionUrl,
        ];
    }
}
