<?php

namespace App\Models;

use App\Enums\Variant;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Variant $variant
 * @property int $year
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Hand> $hands
 */
#[Fillable(['name', 'variant', 'year', 'published_at'])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    /**
     * Get the card's categories, in the order the card prints them.
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    /**
     * Get every hand on the card, in the order the card prints them.
     *
     * @return HasMany<Hand, $this>
     */
    public function hands(): HasMany
    {
        return $this->hasMany(Hand::class)->orderBy('sort_order');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variant' => Variant::class,
            'year' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
