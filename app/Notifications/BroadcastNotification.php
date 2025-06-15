<?php

namespace App\Notifications;

use App\Models\Broadcast;
use CustomFCMChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\AndroidConfig;
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

        // Create a preview text that includes both title and description preview
        $previewText = strlen($this->broadcast->description) > 100 
            ? substr($this->broadcast->description, 0, 100) . '...'
            : $this->broadcast->description;

        // Include both notification and data payload for better compatibility
        return (new FcmMessage(notification: new FcmNotification(
            title: '📢 New Broadcast!',
            body: $this->broadcast->title . "\n" . $previewText,
            image: $this->broadcast->image
        )))
            ->topic('broadcast') // Using "broadcast" topic
            ->data([
                'notification_type' => 'broadcast',
                'broadcast_id' => (string) $this->broadcast->id,
                'click_action' => 'OPEN_BROADCAST',
                'title' => $this->broadcast->title,
                'description' => $this->broadcast->description,
                'created_at' => $this->broadcast->created_at->toISOString(),
                'target_route' => 'broadcast_detail/' . $this->broadcast->id,
                'image' => $this->broadcast->image,
                'author_name' => $this->broadcast->user->name ?? 'Unknown',
                'created_at_formatted' => $this->broadcast->created_at->format('M j, Y g:i A')
            ])
            ->custom([
                'android' => AndroidConfig::fromArray(
                    [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'parkirkan_notification_channel',
                            'image' => $this->broadcast->image,
                            'click_action' => 'OPEN_BROADCAST_NOTIFICATION',
                            'sound' => 'default',
                            'tag' => 'broadcast_' . $this->broadcast->id
                        ]
                    ],
                ),
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
