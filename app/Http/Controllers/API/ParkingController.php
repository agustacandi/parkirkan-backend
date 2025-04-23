<?php

namespace App\Http\Controllers\API;

use App\Models\Parking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ParkingController extends BaseController
{
    public function checkIn(Request $request)
    {
        try {
            // create validation rules
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
                'check_in_image' => 'required|image|mimes:jpeg,png,jpg|max:1024',
            ]);

            // check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // check if vehicle with license plate exists
            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();

            if (!$vehicle) {
                return $this->sendError('Vehicle not found', JsonResponse::HTTP_NOT_FOUND);
            }

            $image = $request->file('check_in_image');
            $image->storeAs('checkin', $image->hashName());

            // create check in record
            $checkIn = Parking::create([
                'user_id' => Auth::id(),
                'vehicle_id' => $vehicle->id,
                'check_in_time' => now(),
                'check_in_image' => $image->hashName(),
            ]);

            // return response
            return $this->sendResponse($checkIn->toArray(), 'Vehicle checked in successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkOut(Request $request)
    {
        try {
            // create validation rules
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
                'check_out_image' => 'required|image|mimes:jpeg,png,jpg|max:1024',
            ]);

            // check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // check if vehicle with license plate exists
            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();

            if (!$vehicle) {
                return $this->sendError('Vehicle not found', JsonResponse::HTTP_NOT_FOUND);
            }

            $isCheckOutConfirmed = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->where("is_check_out_confirmed", true)
                ->exists();

            if (!$isCheckOutConfirmed) {
                return $this->sendError('Check-out not confirmed', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $image = $request->file('check_out_image');
            $image->storeAs('checkout', $image->hashName());

            // create check out record
            $checkOut = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->first();
            if (!$checkOut) {
                return $this->sendError('No check-in record found for this vehicle', JsonResponse::HTTP_NOT_FOUND);
            }
            $checkOut->update([
                'check_out_time' => now(),
                'check_out_image' => $image->hashName(),
                'status' => 'done',
            ]);

            // return response
            return $this->sendResponse($checkOut->toArray(), 'Vehicle checked out successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function confirmCheckOut(Request $request)
    {
        try {
            // create validation rules
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
            ]);
            // check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            // check if vehicle with license plate exists
            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();
            if (!$vehicle) {
                return $this->sendError('Vehicle not found', JsonResponse::HTTP_NOT_FOUND);
            }
            // check if vehicle is checked in
            $isCheckedIn = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->exists();
            if (!$isCheckedIn) {
                return $this->sendError('Vehicle not checked in', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            // confirm check out
            Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->update(['is_check_out_confirmed' => true]);
            // return response
            return $this->sendResponse([], 'Check-out confirmed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserParkingRecords(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 5);

            // get user auth
            $user = Auth::user();

            // get all parking records
            $parkingRecords = Parking::where('user_id', $user->id)->latest()->paginate($perPage);

            // check if parking records exist
            if ($parkingRecords->isEmpty()) {
                return $this->sendError('No parking records found', JsonResponse::HTTP_NOT_FOUND);
            }

            // return response
            return $this->sendResponse($parkingRecords->toArray(), 'Parking records retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getParkingRecords(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 5);

            // get all parking records
            $parkingRecords = Parking::with(['user', 'vehicle'])->latest()->paginate($perPage);

            // check if parking records exist
            if ($parkingRecords->isEmpty()) {
                return $this->sendError('No parking records found', JsonResponse::HTTP_NOT_FOUND);
            }

            // return response
            return $this->sendResponse($parkingRecords->toArray(), 'Parking records retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDashboard()
    {
        try {
            $totalVehicles = Vehicle::count();
            $totalUsers = User::count();
            // data parkir hari ini
            $totalParkings = Parking::whereDate('check_in_time', now())->count();

            // data untuk chart
            $parkings = Parking::selectRaw('DATE(check_in_time) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get();
            $labels = $parkings->pluck('date')->toArray();
            $data = $parkings->pluck('count')->toArray();
            $chartData = [
                'labels' => $labels,
                'data' => $data,
            ];

            return $this->sendResponse([
                'total_vehicles' => $totalVehicles,
                'total_users' => $totalUsers,
                'total_parkings' => $totalParkings,
                'chart_data' => $chartData,
            ], 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function isUserCheckIn(Request $request)
    {
        try {
            // create validation rules
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
            ]);

            // check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // check if vehicle with license plate exists
            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();
            if (!$vehicle) {
                return $this->sendError('Vehicle not found', JsonResponse::HTTP_NOT_FOUND);
            }
            // check if vehicle is checked in
            $isCheckedIn = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->exists();
            return $this->sendResponse(['is_checked_in' => $isCheckedIn], 'Check-in status retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
