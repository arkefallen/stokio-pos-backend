<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Actions\DeleteUser;
use App\Modules\Auth\Actions\LoginUser;
use App\Modules\Auth\Actions\RegisterUser;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        $user = $action->execute($request->validated());

        return response()->json([
            'message' => 'User registered successfully.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Authenticate user and return token
     */
    public function login(LoginRequest $request, LoginUser $action): JsonResponse
    {
        $result = $action->execute(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name', 'default')
        );

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Get the authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    /**
     * Logout (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // Revoke all tokens for this user
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices successfully.',
        ]);
    }

    /**
     * Delete a user (admin only)
     */
    public function destroy(Request $request, User $user, DeleteUser $action): JsonResponse
    {
        $action->execute($user, $request->user());

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * List all users (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->isAdmin()) {
            abort(403, 'Only administrators can view user list.');
        }

        $users = User::select(['id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'is_online' => $user->last_login_at && $user->last_login_at->gt(now()->subMinutes(30)),
                    'last_login_at' => $user->last_login_at?->toIso8601String(),
                    'created_at' => $user->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $users,
            'total' => $users->count(),
        ]);
    }
}

