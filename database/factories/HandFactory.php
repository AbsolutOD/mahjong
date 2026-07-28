<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Hand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hand>
 */
class HandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The default hand is "FF 2222(A) 4444(A) DDDD(B)" — two suits, a pair of
     * flowers, and three joker-eligible kongs.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'card_id' => fn (array $attributes) => Category::query()
                ->whereKey($attributes['category_id'])
                ->firstOrFail()
                ->card_id,
            'sort_order' => fake()->numberBetween(1, 20),
            'points' => fake()->randomElement([25, 30, 35, 40, 45, 50]),
            'concealed' => false,
            'structure' => [
                'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
                'constraints' => [['distinct' => ['A', 'B']]],
                'groups' => [
                    [['t' => 'flower'], ['t' => 'flower']],
                    array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
                    array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 4]),
                    array_fill(0, 4, ['t' => 'dragon', 'suit' => 'B']),
                ],
            ],
        ];
    }

    /**
     * Indicate that the hand may never be exposed.
     */
    public function concealed(): static
    {
        return $this->state(fn (array $attributes) => [
            'concealed' => true,
        ]);
    }
}
