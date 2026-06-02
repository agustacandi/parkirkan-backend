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
    protected readonly ?Notification $notification;
    protected readonly MessageData|array $data;
    protected readonly bool $includeNotification;

    public function __construct(?Notification $notification, MessageData|array $data, bool $includeNotification = true)
    {
        $this->messaging = app('firebase.messaging');
        $this->notification = $notification;
        $this->data = $data;
        $this->includeNotification = $includeNotification;
    }

    public function sendToTopic(string $topic, ?AndroidConfig $androidConfig = null, ?ApnsConfig $apnsConfig = null): void
    {
        $message = CloudMessage::new()
            ->toTopic($topic)
            ->withData($this->data);

        if ($this->includeNotification && $this->notification) {
            $message = $message->withNotification($this->notification);
        }

        if ($androidConfig) {
            $message = $message->withAndroidConfig($androidConfig);
        }

        if ($apnsConfig) {
            $message = $message->withApnsConfig($apnsConfig);
        }

        $this->messaging->send($message);
    }

    public function sendToToken(string $token, ?AndroidConfig $androidConfig = null, ?ApnsConfig $apnsConfig = null): void
    {
        $message = CloudMessage::new()
            ->toToken($token)
            ->withData($this->data);

        if ($this->includeNotification && $this->notification) {
            $message = $message->withNotification($this->notification);
        }

        if ($androidConfig) {
            $message = $message->withAndroidConfig($androidConfig);
        }

        if ($apnsConfig) {
            $message = $message->withApnsConfig($apnsConfig);
        }

        $this->messaging->send($message);
    }
}
