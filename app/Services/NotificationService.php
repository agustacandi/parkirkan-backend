<?php

namespace App\Services;

use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MessageData;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    protected $messaging;
    protected readonly Notification $notification;
    protected readonly MessageData|array $data;

    public function __construct(Notification $notification, MessageData|array $data)
    {
        $this->messaging = app('firebase.messaging');
        $this->notification = $notification;
        $this->data = $data;
    }

    public function sendToTopic(string $topic, ?AndroidConfig $androidConfig = null, ?ApnsConfig $apnsConfig = null): void
    {
        $message = CloudMessage::new()
            ->toTopic($topic)
            ->withNotification($this->notification)
            ->withData($this->data);

        if ($androidConfig) $message->withAndroidConfig($androidConfig);
        if ($apnsConfig) $message->withApnsConfig($apnsConfig);

        $this->messaging->send($message);
    }

    public function sendToToken(string $token, ?AndroidConfig $androidConfig = null, ?ApnsConfig $apnsConfig = null): void
    {
        $message = CloudMessage::new()
            ->toToken($token)
            ->withNotification($this->notification)
            ->withData($this->data);

        if ($androidConfig) $message->withAndroidConfig($androidConfig);
        if ($apnsConfig) $message->withApnsConfig($apnsConfig);

        $this->messaging->send($message);
    }
}
