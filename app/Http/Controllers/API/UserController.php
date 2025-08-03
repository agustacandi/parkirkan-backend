<?php

namespace App\Http\Controllers\API;

use App\Imports\UserImport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            $email = $request->get('email', null);
            $phone = $request->get('phone', null);
            $users = null;

            $query = User::query()->latest();

            if ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            }

            if ($email) {
                $query->where('email', 'like', '%' . $email . '%');
            }

            if ($phone) {
                $query->where('phone', 'like', '%' . $phone . '%');
            }

            $users = $query->paginate($perPage);

            return $this->sendResponse($users->toArray(), 'Users retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|max:20',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'in:user,admin',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed.', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'user',
            ]);

            return $this->sendResponse($user->toArray(), 'User created successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        try {
            return $this->sendResponse($user->toArray(), 'User retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'required|string|max:20',
                'password' => 'nullable|string|min:8|confirmed',
                'role' => 'in:user,admin',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed.', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update user data
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role ?? $user->role,
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return $this->sendResponse($user->fresh()->toArray(), 'User updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function import(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:xlsx,xls,csv|max:2048', // Max 2MB
            ]);

            if ($validator->fails()) {
                return $this->sendError('File validation failed.', $validator->errors());
            }

            // Count users before import
            $userCountBefore = User::count();

            // Handle file import logic here
            $import = new UserImport;
            Excel::import($import, $request->file('file'));

            // Count users after import to get the number of imported users
            $userCountAfter = User::count();
            $importedCount = $userCountAfter - $userCountBefore;

            return $this->sendResponse([
                'imported' => $importedCount,
                'total_users' => $userCountAfter,
                'message' => $importedCount > 0
                    ? "Successfully imported {$importedCount} users."
                    : "No new users were imported. Users may already exist."
            ], "Import completed successfully.");
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Handle validation errors from Excel import
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $errors[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            }

            return $this->sendError('Import validation failed.', [
                'errors' => $errors,
                'details' => 'Please check your file format and data.'
            ]);
        } catch (\Exception $e) {
            return $this->sendError(
                'Import failed: ' . $e->getMessage(),
                ['details' => 'Please check your file format and try again.'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
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
