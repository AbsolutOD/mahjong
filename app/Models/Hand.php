<?php

namespace App\Models;

use App\Data\HandStructure;
use Database\Factories\HandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One line of a card.
 *
 * Only what the card itself prints is stored — its points, its C/X flag and its
 * groups. Everything else the app shows about a hand is derived from the
 * structure, so it can never disagree with the tiles it describes.
 *
 * @property int $id
 * @property int $card_id
 * @property int $category_id
 * @property string $slug
 * @property int $sort_order
 * @property int $points
 * @property bool $concealed
 * @property HandStructure $structure
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Card $card
 * @property-read Category $category
 */
#[Fillable(['card_id', 'category_id', 'slug', 'sort_order', 'points', 'concealed', 'structure'])]
class Hand extends Model
{
    /** @use HasFactory<HandFactory> */
    use HasFactory;

    /**
     * Get the card this hand belongs to.
     *
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Get the category this hand sits under.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'points' => 'integer',
            'concealed' => 'boolean',
            'structure' => HandStructure::class,
        ];
    }
}
