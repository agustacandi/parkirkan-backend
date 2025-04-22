<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'fcm_token' => 'nullable',
            ]);

            if (Auth::attempt($request->only('email', 'password'))) {
                $user = Auth::user();

                if ($request->filled("fcm_token")) {
                    $user->update(['fcm_token' => $request->fcm_token]);
                }

                $user['token'] = $user->createToken('auth_token')->plainTextToken;

                return $this->sendResponse($user, 'User logged in successfully.');
            } else {
                return $this->sendError('Unauthorised.', ['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            return $this->sendError('Internal Server Error', ['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function loginAdmin(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // check if user is admin
            $user = User::where('email', $credentials['email'])->first();
            if ($user && $user->role !== 'admin') {
                return $this->sendError('Unauthorised.', ['error' => 'You are not authorized to access this resource.'], JsonResponse::HTTP_UNAUTHORIZED);
            }

            if (Auth::attempt(($credentials))) {
                $user = Auth::user();
                $user['token'] = $user->createToken('auth_token')->plainTextToken;

                return $this->sendResponse($user, 'User logged in successfully.');
            } else {
                return $this->sendError('Unauthorised.', ['error' => 'Invalid credentials.'], JsonResponse::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            return $this->sendError('Internal Server Error', ['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse([], 'User logged out successfully.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        ]);
        $user = Auth::user();
        if (!password_verify($request->old_password, $user->password)) {
            return $this->sendError('Unauthorised.', ['error' => 'Old password is incorrect.'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $user->password = bcrypt($request->new_password);
        $user->save();
        return $this->sendResponse([], 'Password changed successfully.');
    }
}
