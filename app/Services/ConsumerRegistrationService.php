<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ConsumerRegistrationService
{
    private const PHOTO_DISK = 'consumer_photos';

    /**
     * @param  array{name: string, document: string, phone: string, email: string, password: string}  $data
     */
    public function create(int $businessId, array $data, ?UploadedFile $photo): User
    {
        $photoPath = null;

        try {
            if ($photo !== null) {
                $storedPath = $photo->store("businesses/{$businessId}", self::PHOTO_DISK);

                if (! is_string($storedPath)) {
                    throw new RuntimeException('Não foi possível armazenar a foto do consumidor.');
                }

                $photoPath = $storedPath;
            }

            return DB::connection('mysql')->transaction(fn (): User => User::query()->create([
                'business_id' => $businessId,
                'name' => $data['name'],
                'document' => $data['document'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'photo_path' => $photoPath,
                'password' => Hash::make($data['password']),
            ]));
        } catch (Throwable $exception) {
            if (is_string($photoPath)) {
                Storage::disk(self::PHOTO_DISK)->delete($photoPath);
            }

            throw $exception;
        }
    }
}
