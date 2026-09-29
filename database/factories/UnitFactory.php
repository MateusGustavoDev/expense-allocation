<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
final class UnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Unidade '.fake()->unique()->city(),
            'slug' => fn (array $attributes): string => Str::slug($attributes['name']),
        ];
    }
}
