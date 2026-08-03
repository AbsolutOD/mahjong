<?php

namespace App\Enums;

use App\Data\Tiles\TileFace;

/**
 * The colour a tile face is drawn in.
 *
 * Tone is never the only thing telling two faces apart — see the glyph and word
 * band on {@see TileFace} — and `Unbound` is reserved: grey, and
 * only grey, means the card has not chosen a suit for this slot yet.
 */
enum FaceTone: string
{
    case Dots = 'dots';
    case Bams = 'bams';
    case Craks = 'craks';
    case Wind = 'wind';
    case Red = 'red';
    case Green = 'green';
    case Soap = 'soap';
    case Flower = 'flower';
    case Joker = 'joker';
    case Unbound = 'unbound';

    /**
     * Get the tone a suit is drawn in.
     */
    public static function forSuit(Suit $suit): self
    {
        return match ($suit) {
            Suit::Dots => self::Dots,
            Suit::Bams => self::Bams,
            Suit::Craks => self::Craks,
        };
    }

    /**
     * Get the CSS colour this tone paints with.
     *
     * Every tone resolves through a theme variable rather than a literal, so a
     * dark or high-contrast palette is a stylesheet change and never a code one.
     */
    public function ink(): string
    {
        return "var(--color-tile-{$this->value})";
    }
}
