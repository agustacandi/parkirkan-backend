<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class NotificationTopic extends Model
{
    use Notifiable;

    protected $fillable = ['topic'];

    /**
     * Route notifications for the FCM channel.
     */
    public function routeNotificationForFcm()
    {
        // Return null because we're using topic messaging
        // The topic will be set in the notification itself
        return null;
    }
} 