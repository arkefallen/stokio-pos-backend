<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUser
{
    /**
     * Register a new user
     *
     * @param array{name: string, email: string, password: string, role?: string} $data
     * @return User
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // Will be auto-hashed by model cast
                'role' => $data['role'] ?? User::ROLE_CASHIER,
                'is_active' => true,
            ]);

            return $user;
        });
    }
}
