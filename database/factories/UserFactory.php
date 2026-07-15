<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'school_id' => fake()->unique()->numerify('TEST########'),
            'password_hash' => static::$password ??= Hash::make('password'),
            'display_name' => fake()->name(),
            'school_name' => 'ศกร.ระดับตำบลทดสอบ',
            'district' => 'เสนา',
            'province' => 'พระนครศรีอยุธยา',
            'role' => 'subdistrict_admin',
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
