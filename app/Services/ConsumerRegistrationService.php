<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConsumerRegistrationService
{
    /**
     * @param  array{name: string, document: string, phone: string, email: string, password: string}  $data
     */
    public function create(int $businessId, array $data): User
    {
        return DB::connection('mysql')->transaction(fn (): User => User::query()->create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'document' => $data['document'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]));
    }
}
