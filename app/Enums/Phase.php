<?php

namespace App\Enums;

/**
 * The three PRD phases, in the order they shipped (issue #53).
 *
 * The one place a phase is named: the ways in and every nav loop over these,
 * so the front door and the shell cannot drift into describing different
 * products. The value is the phase's route name.
 */
enum Phase: string
{
    case LineDecoder = 'card';
    case HandMatcher = 'matcher';
    case PracticeAndQuiz = 'practice';

    /**
     * Get the phase's name, as every nav and its page's title print it.
     */
    public function label(): string
    {
        return match ($this) {
            self::LineDecoder => __('Line Decoder'),
            self::HandMatcher => __('Hand Matcher'),
            self::PracticeAndQuiz => __('Practice & Quiz'),
        };
    }

    /**
     * Get the line the ways in say about the phase.
     */
    public function blurb(): string
    {
        return match ($this) {
            self::LineDecoder => __('Read any line on the card as tiles, in plain English: what each group is, which suits have to differ, and where a joker is legal.'),
            self::HandMatcher => __('Lay out your rack and see which lines you are closest to, how many tiles away each one is, which tiles you are still short of, and which to pass in the Charleston.'),
            self::PracticeAndQuiz => __('Drill the things that cost you the game: whether a hand needs one suit or three, and whether that joker is actually allowed.'),
        };
    }

    /**
     * Get the Flux icon the signed-in shell shows beside the phase.
     */
    public function icon(): string
    {
        return match ($this) {
            self::LineDecoder => 'book-open',
            self::HandMatcher => 'puzzle-piece',
            self::PracticeAndQuiz => 'academic-cap',
        };
    }

    /**
     * Get the URL of the phase's page.
     */
    public function url(): string
    {
        return route($this->value);
    }

    /**
     * Determine whether the current request is for the phase's page.
     */
    public function isCurrent(): bool
    {
        return request()->routeIs($this->value);
    }
}
