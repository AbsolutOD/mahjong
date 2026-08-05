<?php

namespace App\Enums;

/**
 * What a rule tag is about, carried as the badge colour it prints in.
 *
 * The tone names the subject rather than the hue, so a tag never has to know
 * about Flux; the value is the colour only because the panel has to say one.
 */
enum TagTone: string
{
    case Neutral = 'zinc';
    case Suit = 'sky';
    case Joker = 'amber';
    case Forbidden = 'red';
    case Concealed = 'purple';
}
