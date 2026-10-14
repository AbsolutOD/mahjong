<?php

namespace App\Enums;

/**
 * The two things the practice quiz drills (issue #18).
 *
 * These are the two the landing page promises and the two that cost a game at
 * the table: misreading how many suits a line needs, and calling a joker into
 * a group that cannot take one.
 */
enum QuestionKind: string
{
    case SuitCount = 'suits';
    case JokerLegality = 'jokers';

    /**
     * Get the question the quiz asks about the line on show.
     */
    public function prompt(): string
    {
        return match ($this) {
            self::SuitCount => 'How many different suits does this line need?',
            self::JokerLegality => 'May a joker stand in for a tile in the marked group?',
        };
    }
}
