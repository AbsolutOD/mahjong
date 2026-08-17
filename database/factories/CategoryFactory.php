<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'name' => ucfirst(fake()->unique()->word()).' Hands',
            'slug' => fake()->unique()->slug(),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
