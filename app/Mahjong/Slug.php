<?php

namespace App\Mahjong;

use Illuminate\Support\Str;

/**
 * The stable public names of one card's parts.
 *
 * Decoder URLs are built from slugs rather than row ids, because reseeding a
 * card deletes and recreates every row: ids change on every deploy, so a link
 * built from one silently resolves to a different hand. A slug derives from
 * what the card prints, so it survives a reseed untouched.
 *
 * One of these is made per import and remembers every slug it has handed out,
 * because a slug only has to be unique within the card that prints it.
 */
class Slug
{
    /** @var list<string> */
    private array $categories = [];

    /** @var list<string> */
    private array $hands = [];

    /**
     * Get the slug of a section, from the name the card prints above it.
     */
    public function forCategory(string $name): string
    {
        return $this->categories[] = $this->distinct(Str::slug($name), $this->categories);
    }

    /**
     * Get the slug of a line, from the line itself.
     *
     * A concealed hand is marked, rather than left to be told apart by its
     * position: cards print the same tiles both exposed and concealed, and a
     * suffix decided by print order would move the moment the card is reordered.
     */
    public function forLine(string $line, bool $concealed): string
    {
        return $this->hands[] = $this->distinct(
            Str::slug($line).($concealed ? '-c' : ''),
            $this->hands,
        );
    }

    /**
     * Set a slug apart from the ones already handed out.
     *
     * @param  list<string>  $taken
     */
    private function distinct(string $slug, array $taken): string
    {
        if (! in_array($slug, $taken, strict: true)) {
            return $slug;
        }

        $suffix = 2;

        while (in_array("{$slug}-{$suffix}", $taken, strict: true)) {
            $suffix++;
        }

        return "{$slug}-{$suffix}";
    }
}
