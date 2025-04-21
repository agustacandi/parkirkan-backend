<?php

namespace App\Http\Controllers\API;

use App\Imports\UserImport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        try {
            // get per_page param
            $perPage = $request->get('per_page', 5);
            $name = $request->get('name', null);
            $users = null;

            if ($name) {
                $users = User::where('name', 'like', '%' . $name . '%')->latest()->paginate($perPage);
            } else {
                $users = User::latest()->paginate($perPage);
            }

            return $this->sendResponse($users->toArray(), 'Users retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function import(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:xlsx,xls,csv',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Handle file import logic here
            Excel::import(new UserImport, $request->file('file'));

            return $this->sendResponse([], 'Users imported successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(User $user)
    {
        try {
            $user->delete();

            return $this->sendResponse([], 'User deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateFcmToken(User $user)
    {
        try {
            $user->update([
                'fcm_token' => request('fcm_token')
            ]);
            return $this->sendResponse([], 'FCM token updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
