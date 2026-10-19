<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => 1,
            'name' => fake()->name(),
            'document' => $this->validCpf(),
            'phone' => fake()->numerify('###########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'photo_path' => null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    private function validCpf(): string
    {
        do {
            $cpf = fake()->numerify('#########');
        } while (preg_match('/^(\d)\1{8}$/', $cpf) === 1);

        for ($checkDigitIndex = 9; $checkDigitIndex <= 10; $checkDigitIndex++) {
            $sum = 0;

            for ($digitIndex = 0; $digitIndex < $checkDigitIndex; $digitIndex++) {
                $sum += (int) $cpf[$digitIndex] * (($checkDigitIndex + 1) - $digitIndex);
            }

            $remainder = $sum % 11;
            $cpf .= (string) ($remainder < 2 ? 0 : 11 - $remainder);
        }

        return $cpf;
    }
}
