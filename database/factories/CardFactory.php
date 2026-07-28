<?php

namespace Database\Factories;

use App\Enums\Variant;
use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()).' Practice Card',
            'variant' => Variant::American,
            'year' => fake()->numberBetween(2020, 2030),
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the card has not been published yet.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }
}
