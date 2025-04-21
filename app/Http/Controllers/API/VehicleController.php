<?php

namespace App\Http\Controllers\API;

use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VehicleController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $vehicles = Vehicle::where('user_id', $user->id)->latest()->paginate(5);

            return $this->sendResponse($vehicles->toArray(), 'Vehicles retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            //get user auth
            $user = Auth::user();

            //define validation rules
            $validator = Validator::make($request->all(), [
                'name'         => 'required',
                'license_plate'   => 'required|unique:vehicles,license_plate',
                'image'         => 'required|image|mimes:jpeg,png,jpg|max:1024',
            ]);

            //check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            //upload image
            $image = $request->file('image');
            $image->storeAs('vehicles', $image->hashName());

            //create vehicle
            $vehicle = Vehicle::create([
                'name'         => $request->name,
                'license_plate'   => $request->license_plate,
                'image'         => $image->hashName(),
                'user_id'       => $user->id
            ]);

            return $this->sendResponse($vehicle->toArray(), 'Vehicle added successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        try {
            if (!$vehicle) {
                return $this->sendError('Vehicle not found.', [], JsonResponse::HTTP_NOT_FOUND);
            }
            return $this->sendResponse($vehicle->toArray(), 'Vehicle retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        try {
            //define validation rules
            $validator = Validator::make($request->all(), [
                'name'         => 'required',
                'license_plate'   => 'required',
                'image'         => 'image|mimes:jpeg,png,jpg|max:1024',
            ]);

            //check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 422);
            }

            if ($vehicle) {

                //check if image is not empty
                if ($request->hasFile('image')) {

                    //delete old image
                    Storage::delete('vehicles/' . basename($vehicle->image));

                    //upload image
                    $image = $request->file('image');
                    $image->storeAs('vehicles', $image->hashName());

                    //update vehicle with new image
                    $vehicle->update([
                        'name'         => $request->name,
                        'license_plate'   => $request->license_plate,
                        'image'         => $image->hashName(),
                    ]);
                } else {

                    //update vehicle without image
                    $vehicle->update([
                        'name'         => $request->name,
                        'license_plate'   => $request->license_plate,
                    ]);
                }


                return $this->sendResponse($vehicle->toArray(), 'Vehicle updated successfully.');
            } else {
                return $this->sendError('Vehicle not found.', [], JsonResponse::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        try {
            if ($vehicle) {
                if ($vehicle->image) {
                    Storage::disk('public')->delete($vehicle->image);
                }
                $vehicle->delete();
                return $this->sendResponse([], 'Vehicle deleted successfully.');
            } else {
                return $this->sendError('Vehicle not found.', [], JsonResponse::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            return $this->sendError('Error deleting vehicle.', [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
