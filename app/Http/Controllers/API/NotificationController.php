<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use App\Notifications\CheckOutAlert;
use Illuminate\Http\JsonResponse;

class NotificationController extends BaseController
{
    public function sendNotification(User $user)
    {
        try {
            $user->notify(new CheckOutAlert());
            return $this->sendResponse([], 'Notification sent successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
