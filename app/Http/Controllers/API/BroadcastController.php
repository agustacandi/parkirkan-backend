<?php

namespace App\Http\Controllers\API;

use App\Models\Broadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BroadcastController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $broadcasts = Broadcast::latest()->paginate(5);
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

            return $this->sendResponse($broadcast->toArray(), 'Broadcast added successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error adding broadcast.', [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Broadcast $broadcast)
    {
        try {
            if (!$broadcast) {
                return $this->sendError('Briadcast not found.', [], JsonResponse::HTTP_NOT_FOUND);
            }
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
