<?php

namespace App\Http\Controllers\API;

use App\Models\Broadcast;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\Notification;

class BroadcastController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $broadcasts = Broadcast::with(['user'])->latest()->paginate(5);
            return $this->sendResponse($broadcasts->toArray(), 'Broadcasts retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving broadcasts.', [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // get user auth
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'title'         => 'required',
                'description'   => 'required',
                'image'         => 'image|mimes:jpeg,png,jpg|max:1024',
            ]);

            //check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $broadcast = null;

            if ($request->hasFile('image')) {
                //upload image
                $image = $request->file('image');
                $image->storeAs('broadcasts', $image->hashName());
                //create broadcast
                $broadcast = Broadcast::create([
                    'title'         => $request->title,
                    'description'   => $request->description,
                    'image'         => $image->hashName(),
                    'user_id'       => $user->id
                ]);
            } else {
                //create broadcast
                $broadcast = Broadcast::create([
                    'title'         => $request->title,
                    'description'   => $request->description,
                    'user_id'       => $user->id
                ]);
            }

            $previewText = strlen($broadcast->description) > 100
                ? substr($broadcast->description, 0, 100) . '...'
                : $broadcast->description;

            // Send notification directly to FCM topic without using model
            $notification = Notification::create(
                title: '📢 New Broadcast!',
                body: $broadcast->title . "\n" . $previewText,
                imageUrl: $broadcast->image
            );

            $notificationData = [
                'notification_type' => 'broadcast',
                'broadcast_id' => (string) $broadcast->id,
                'click_action' => 'OPEN_BROADCAST',
                'title' => $broadcast->title,
                'description' => $broadcast->description,
                'created_at' => $broadcast->created_at->toISOString(),
                'target_route' => 'broadcast_detail/' . $broadcast->id,
                'image' => $broadcast->image,
                'author_name' => $broadcast->user->name ?? 'Unknown',
                'created_at_formatted' => $broadcast->created_at->format('M j, Y g:i A')
            ];

            $notificationService = new NotificationService($notification, $notificationData);
            $notificationService->sendToTopic('broadcast', AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'parkirkan_notification_channel',
                    'image' => $broadcast->image,
                    'click_action' => 'OPEN_BROADCAST_NOTIFICATION',
                    'sound' => 'default',
                    'tag' => 'broadcast_' . $broadcast->id
                ]
            ]));

            Log::info('Broadcast notification sent successfully', [
                'broadcast_id' => $broadcast->id,
                'queue_connection' => config('queue.default')
            ]);
            // Load the user relationship before returning response
            $broadcast->load('user');

            return $this->sendResponse($broadcast->toArray(), 'Broadcast added successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error adding broadcast: ' . $e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Broadcast $broadcast)
    {
        try {
            if (!$broadcast) {
                return $this->sendError('Broadcast not found.', [], JsonResponse::HTTP_NOT_FOUND);
            }

            // Load the user relationship to include user data in response
            $broadcast->load('user');

            return $this->sendResponse($broadcast->toArray(), 'Broadcast retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Broadcast $broadcast)
    {
        try {
            //define validation rules
            $validator = Validator::make($request->all(), [
                'title'         => 'required',
                'description'   => 'required',
                'image'         => 'image|mimes:jpeg,png,jpg|max:1024',
            ]);

            //check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 422);
            }

            if ($broadcast) {

                //check if image is not empty
                if ($request->hasFile('image')) {

                    //delete old image
                    if ($broadcast->image) {
                        Storage::delete('broadcasts/' . basename($broadcast->image));
                    }

                    //upload image
                    $image = $request->file('image');
                    $image->storeAs('broadcasts', $image->hashName());

                    //update broadcast with new image
                    $broadcast->update([
                        'title'         => $request->title,
                        'description'   => $request->description,
                        'image'         => $image->hashName(),
                    ]);
                } else {

                    //update vehicle without image
                    $broadcast->update([
                        'title'         => $request->title,
                        'description'   => $request->description,
                    ]);
                }


                // Load the user relationship before returning response
                $broadcast->load('user');

                return $this->sendResponse($broadcast->toArray(), 'Broadcast updated successfully.');
            } else {
                return $this->sendError('Broadcast not found.', [], JsonResponse::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Broadcast $broadcast)
    {
        try {
            if ($broadcast) {

                if ($broadcast->image) {
                    Storage::disk('public')->delete($broadcast->image);
                }

                $broadcast->delete();
                return $this->sendResponse([], 'Broadcast deleted successfully.');
            } else {
                return $this->sendError('Broadcast not found.', [], JsonResponse::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            return $this->sendError('Error deleting broadcast.', [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
