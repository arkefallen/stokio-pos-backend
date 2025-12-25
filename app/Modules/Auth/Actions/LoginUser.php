<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUser
{
    /**
     * Authenticate user and return access token
     *
     * @param string $email
     * @param string $password
     * @param string $deviceName
     * @return array{user: User, token: string}
     * @throws ValidationException
     */
    public function execute(string $email, string $password, string $deviceName = 'default'): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact an administrator.'],
            ]);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Create new token
        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
