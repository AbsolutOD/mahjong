<?php

use App\Actions\Cards\ImportCard;
use App\Enums\Variant;
use App\Exceptions\SeedMismatch;
use App\Models\Card;
use App\Models\Hand;

/**
 * The authoring array for a two-category card.
 *
 * @return array<string, mixed>
 */
function authoredCard(): array
{
    return [
        'name' => 'Test Practice Card',
        'variant' => 'american',
        'year' => 2026,
        'categories' => [
            [
                'name' => 'Evens',
                'hands' => [
                    [
                        'line' => 'FF 2222(A) 4444(A) DDDD(B)',
                        'points' => 25,
                        'concealed' => false,
                        'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
                        'constraints' => [['distinct' => ['A', 'B']]],
                        'groups' => [
                            [['t' => 'flower'], ['t' => 'flower']],
                            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 2]),
                            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 4]),
                            array_fill(0, 4, ['t' => 'dragon', 'suit' => 'B']),
                        ],
                    ],
                    [
                        'line' => 'FF 6666(A) 8888(A) 88(B) DD(B)',
                        'points' => 30,
                        'concealed' => true,
                        'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
                        'constraints' => [['distinct' => ['A', 'B']]],
                        'groups' => [
                            [['t' => 'flower'], ['t' => 'flower']],
                            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 6]),
                            array_fill(0, 4, ['t' => 'num', 'suit' => 'A', 'n' => 8]),
                            array_fill(0, 2, ['t' => 'num', 'suit' => 'B', 'n' => 8]),
                            array_fill(0, 2, ['t' => 'dragon', 'suit' => 'B']),
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Winds and Dragons',
                'hands' => [
                    [
                        'line' => 'NEWS DDDD(A) DDDD(B) NN',
                        'points' => 35,
                        'concealed' => false,
                        'variables' => ['A' => ['kind' => 'suit'], 'B' => ['kind' => 'suit']],
                        'constraints' => [['distinct' => ['A', 'B']]],
                        'groups' => [
                            [['t' => 'wind', 'w' => 'N'], ['t' => 'wind', 'w' => 'E'], ['t' => 'wind', 'w' => 'W'], ['t' => 'wind', 'w' => 'S']],
                            array_fill(0, 4, ['t' => 'dragon', 'suit' => 'A']),
                            array_fill(0, 4, ['t' => 'dragon', 'suit' => 'B']),
                            array_fill(0, 2, ['t' => 'wind', 'w' => 'N']),
                        ],
                    ],
                ],
            ],
        ],
    ];
}

test('a card is imported with its categories and hands', function () {
    $card = app(ImportCard::class)->handle(authoredCard());

    expect($card->name)->toBe('Test Practice Card')
        ->and($card->variant)->toBe(Variant::American)
        ->and($card->year)->toBe(2026)
        ->and($card->categories)->toHaveCount(2)
        ->and($card->hands)->toHaveCount(3);
});

test('categories and hands are ordered by their place in the authoring file', function () {
    $card = app(ImportCard::class)->handle(authoredCard());

    expect($card->categories->pluck('name')->all())->toBe(['Evens', 'Winds and Dragons'])
        ->and($card->categories->first()->hands->pluck('points')->all())->toBe([25, 30]);
});

test('each hand keeps the points and concealed flag the card prints', function () {
    $card = app(ImportCard::class)->handle(authoredCard());

    $hand = $card->hands()->where('points', 30)->sole();

    expect($hand->concealed)->toBeTrue()
        ->and($hand->structure->tileCount())->toBe(14);
});

test('a hand whose structure disagrees with its line aborts the import', function () {
    $data = authoredCard();
    $data['categories'][0]['hands'][0]['line'] = 'FF 2222(A) 4444(A) DDDD(A)';

    app(ImportCard::class)->handle($data);
})->throws(
    SeedMismatch::class,
    'Hand [FF 2222(A) 4444(A) DDDD(A)] in category [Evens] renders as [FF 2222(A) 4444(A) DDDD(B)].'
);

test('a card is left untouched when one of its hands fails to match', function () {
    $data = authoredCard();
    $data['categories'][1]['hands'][0]['line'] = 'NEWS DDDD(A) DDDD(B) SS';

    try {
        app(ImportCard::class)->handle($data);
    } catch (SeedMismatch) {
        // The import is expected to abort; what matters is what it left behind.
    }

    expect(Card::count())->toBe(0)
        ->and(Hand::count())->toBe(0);
});

test('importing the same card again replaces it rather than duplicating it', function () {
    app(ImportCard::class)->handle(authoredCard());
    app(ImportCard::class)->handle(authoredCard());

    expect(Card::count())->toBe(1)
        ->and(Hand::count())->toBe(3);
});

test('a hand that does not describe a legal structure aborts the import', function () {
    $data = authoredCard();
    array_pop($data['categories'][0]['hands'][0]['groups']);

    app(ImportCard::class)->handle($data);
})->throws(InvalidArgumentException::class, 'A hand must hold exactly 14 tiles, found 10.');

test('a category is addressed by a slug of the name the card prints', function () {
    $card = app(ImportCard::class)->handle(authoredCard());

    expect($card->categories->pluck('slug')->all())->toBe(['evens', 'winds-and-dragons']);
});

test('a hand is addressed by a slug of the line it prints', function () {
    $card = app(ImportCard::class)->handle(authoredCard());

    expect($card->hands()->where('points', 25)->sole()->slug)->toBe('ff-2222a-4444a-ddddb');
});

test('a concealed line is slugged apart from its exposed twin, whichever is printed first', function () {
    $data = authoredCard();
    $exposed = $data['categories'][0]['hands'][0];

    /** The concealed twin is printed first, to prove print order does not decide the slug. */
    $data['categories'][0]['hands'] = [
        [...$exposed, 'concealed' => true, 'points' => 50],
        $exposed,
    ];

    $card = app(ImportCard::class)->handle($data);

    expect($card->hands()->where('concealed', true)->sole()->slug)->toBe('ff-2222a-4444a-ddddb-c')
        ->and($card->hands()->where('points', 25)->sole()->slug)->toBe('ff-2222a-4444a-ddddb');
});

test('lines that print the same are told apart in the order the card prints them', function () {
    $data = authoredCard();
    $data['categories'][0]['hands'] = [
        $data['categories'][0]['hands'][0],
        $data['categories'][0]['hands'][0],
    ];

    $card = app(ImportCard::class)->handle($data);

    expect($card->categories->first()->hands->pluck('slug')->all())
        ->toBe(['ff-2222a-4444a-ddddb', 'ff-2222a-4444a-ddddb-2']);
});

test('slugs survive a reseed even though row ids do not', function () {
    $before = app(ImportCard::class)->handle(authoredCard())->hands()->orderBy('id')->pluck('slug', 'id');
    $after = app(ImportCard::class)->handle(authoredCard())->hands()->orderBy('id')->pluck('slug', 'id');

    expect($after->values()->all())->toBe($before->values()->all())
        ->and($after->keys()->all())->not->toBe($before->keys()->all());
});
