<?php

use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kreait\Firebase\Messaging\MulticastSendReport;
use NotificationChannels\Fcm\FcmChannel;

final class CustomFCMChannel extends FcmChannel
{
    public function send(mixed $notifiable, Notification $notification): ?Collection
    {
        $fcmMessage = $notification->toFcm($notifiable);

        // If message has a topic then send it directly
        if (!is_null($fcmMessage->topic)) {
            return collect(($fcmMessage->client ?? $this->client)->send($fcmMessage));
        }

        $tokens = Arr::wrap($notifiable->routeNotificationFor('fcm', $notification));

        if (empty($tokens)) {
            return null;
        }

        return Collection::make($tokens)
            ->chunk(self::TOKENS_PER_REQUEST)
            ->map(fn($tokens) => ($fcmMessage->client ?? $this->client)->sendMulticast($fcmMessage, $tokens->all()))
            ->map(fn(MulticastSendReport $report) => $this->checkReportForFailures($notifiable, $notification, $report));
    }
}
