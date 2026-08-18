<?php

use App\Console\Commands\Release;
use Database\Seeders\CardSeeder;

/**
 * A release runs on the deployed application, where nothing can be undone, so
 * what it runs is pinned here rather than typed into a form on a dashboard.
 *
 * The steps are asserted rather than executed: `optimize` writes real caches
 * into bootstrap/cache, which a test has no business leaving behind.
 */
test('a release migrates, reseeds the card, then warms and clears the caches', function () {
    expect(collect(Release::STEPS)->map(fn (array $step): string => $step[0])->all())
        ->toBe(['migrate', 'db:seed', 'optimize', 'cache:clear']);
});

test('a release names the card seeder, because the default one plants a test account', function () {
    $seed = collect(Release::STEPS)->firstWhere(fn (array $step): bool => $step[0] === 'db:seed');

    expect($seed[1]['--class'])->toBe(CardSeeder::class);
});

test('every step of a release is forced, because a deploy has nobody to answer prompts', function () {
    $interactive = collect(Release::STEPS)
        ->filter(fn (array $step): bool => in_array($step[0], ['migrate', 'db:seed'], strict: true))
        ->reject(fn (array $step): bool => ($step[1]['--force'] ?? false) === true);

    expect($interactive)->toBeEmpty();
});
