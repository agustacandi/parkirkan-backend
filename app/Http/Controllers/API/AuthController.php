<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    // public function register(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required',
    //         'email' => 'required|email',
    //         'password' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError('Validation Error.', $validator->errors());
    //     }

    //     $input = $request->all();
    //     $input['password'] = bcrypt($input['password']);
    //     $user = User::create($input);
    //     $success['token'] = $user->createToken('MyApp')->plainTextToken;
    //     $success['name'] = $user->name;

    //     return $this->sendResponse($success, 'User registered successfully.');
    // }

    /**
     * Handle user login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'fcm_token' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors(),
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $credentials = $request->only(['email', 'password']);

            if (Auth::attempt($credentials)) {
                $user = Auth::user();

                // Update FCM token if provided
                if ($request->filled('fcm_token')) {
                    $user->update(['fcm_token' => $request->fcm_token]);
                }

                $user['token'] = $user->createToken('auth_token')->plainTextToken;

                return $this->sendResponse($user, 'User logged in successfully.');
            }

            return $this->sendError(
                'Unauthorised.',
                ['error' => 'Invalid credentials'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        } catch (\Exception $e) {
            Log::error('Login error', ['error' => $e->getMessage(), 'email' => $request->email]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle admin login
     */
    public function loginAdmin(Request $request): JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');

            // Check if user exists and is admin
            $user = User::where('email', $credentials['email'])->first();
            if ($user && !$user->isAdmin()) {
                return $this->sendError(
                    'Unauthorised.',
                    ['error' => 'You are not authorized to access this resource.'],
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $user['token'] = $user->createToken('auth_token')->plainTextToken;

                return $this->sendResponse($user, 'Admin logged in successfully.');
            }

            return $this->sendError(
                'Unauthorised.',
                ['error' => 'Invalid credentials.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        } catch (\Exception $e) {
            Log::error('Admin login error', ['error' => $e->getMessage(), 'email' => $request->email]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->sendResponse([], 'User logged out successfully.');
        } catch (\Exception $e) {
            Log::error('Logout error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle password change
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required',
                'new_password' => 'required|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors(),
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $user = Auth::user();

            if (!password_verify($request->old_password, $user->password)) {
                return $this->sendError(
                    'Unauthorised.',
                    ['error' => 'Old password is incorrect.'],
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }

            $user->update(['password' => bcrypt($request->new_password)]);

            Log::info('Password changed', ['user_id' => $user->id]);

            return $this->sendResponse([], 'Password changed successfully.');
        } catch (\Exception $e) {
            Log::error('Change password error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
