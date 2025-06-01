<?php

namespace App\Notifications;

use App\Models\Broadcast;
use CustomFCMChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class BroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $broadcast;

    /**
     * Create a new notification instance.
     */
    public function __construct(Broadcast $broadcast)
    {
        $this->broadcast = $broadcast;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [CustomFCMChannel::class];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable): FcmMessage
    {
        Log::info('Sending broadcast notification', [
            'broadcast_id' => $this->broadcast->id,
            'title' => $this->broadcast->title
        ]);

        // Include both notification and data payload for better compatibility
        return (new FcmMessage(notification: new FcmNotification(
            title: '📢 Broadcast Baru!',
            body: $this->broadcast->title,
            image: $this->broadcast->image
        )))
            ->topic('broadcast') // Menggunakan topic "broadcast"
            ->data([
                'notification_type' => 'broadcast',
                'broadcast_id' => (string) $this->broadcast->id,
                'click_action' => 'OPEN_BROADCAST',
                'title' => $this->broadcast->title,
                'description' => $this->broadcast->description,
                'created_at' => $this->broadcast->created_at->toISOString(),
                'target_route' => 'broadcast_detail/' . $this->broadcast->id,
                'image' => $this->broadcast->image
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'broadcast_id' => $this->broadcast->id,
            'title' => $this->broadcast->title,
            'description' => $this->broadcast->description,
            'image' => $this->broadcast->image,
            'created_at' => $this->broadcast->created_at
        ];
    }
}

