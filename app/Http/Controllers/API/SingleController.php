<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Broadcast;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SingleController extends BaseController
{
    public function getAllVehicles(Request $request)
    {
        try {
            $user = Auth::user();
            $vehicles = Vehicle::where('user_id', $user->id)->latest()->get();
            return $this->sendResponse($vehicles->toArray(), 'Vehicles retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving vehicles: ' . $e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllBroadcasts(Request $request)
    {
        try {
            $per_page = $request->get('per_page', 5);
            $broadcasts = Broadcast::with(['user'])->latest()->paginate($per_page);
            return $this->sendResponse($broadcasts->toArray(), 'Broadcasts retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving broadcasts: ' . $e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
