<?php

use App\Data\Tiles\SuitAssignment;
use App\Enums\Suit;

test('nothing is bound by default', function () {
    $assignment = SuitAssignment::none();

    expect($assignment->for('A'))->toBeNull()
        ->and($assignment->isEmpty())->toBeTrue();
});

test('binding a variable leaves the original untouched', function () {
    $empty = SuitAssignment::none();

    $bound = $empty->bind('A', Suit::Dots);

    expect($bound->for('A'))->toBe(Suit::Dots)
        ->and($empty->for('A'))->toBeNull();
});

test('an unbound variable stays null while its siblings are bound', function () {
    $assignment = SuitAssignment::none()->bind('A', Suit::Dots);

    expect($assignment->for('B'))->toBeNull()
        ->and($assignment->isEmpty())->toBeFalse();
});

test('variables are bound in one call from a map', function () {
    $assignment = SuitAssignment::of(['A' => Suit::Dots, 'B' => Suit::Bams]);

    expect($assignment->for('A'))->toBe(Suit::Dots)
        ->and($assignment->for('B'))->toBe(Suit::Bams);
});

test('two variables may not share a suit, because the card colours mean distinctness', function () {
    SuitAssignment::of(['A' => Suit::Dots, 'B' => Suit::Dots]);
})->throws(InvalidArgumentException::class);

test('rebinding a variable to a suit another already holds is rejected', function () {
    SuitAssignment::none()->bind('A', Suit::Dots)->bind('B', Suit::Dots);
})->throws(InvalidArgumentException::class);

test('rebinding a variable to the suit it already holds is allowed', function () {
    $assignment = SuitAssignment::none()->bind('A', Suit::Dots)->bind('A', Suit::Dots);

    expect($assignment->for('A'))->toBe(Suit::Dots);
});
