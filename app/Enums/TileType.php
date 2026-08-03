<?php

namespace App\Enums;

/**
 * The kinds of tile the app talks about.
 *
 * Two overlapping vocabularies share this enum. A card's tile spec may be a
 * zero, which is the soap standing in as a numeral and has no face of its own;
 * a physical tile may be a joker, which no card line ever prints.
 */
enum TileType: string
{
    case Number = 'num';
    case Dragon = 'dragon';
    case Wind = 'wind';
    case Flower = 'flower';
    case Zero = 'zero';
    case Joker = 'joker';
}
