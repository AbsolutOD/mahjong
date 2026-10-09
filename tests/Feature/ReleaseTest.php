<?php

use App\Console\Commands\Release;
use Database\Seeders\CardSeeder;

/**
 * A release runs on the deployed application, where nothing can be undone, so
 * what it runs is pinned here rather than typed into a form on a dashboard.
 *
 * The steps are asserted rather than executed, so the suite never migrates or
 * reseeds the database it is running against.
 */
test('a release migrates, then reseeds the card', function () {
    expect(collect(Release::STEPS)->map(fn (array $step): string => $step[0])->all())
        ->toBe(['migrate', 'db:seed']);
});

test('a release writes nothing to disk, because Cloud discards what deploy commands write', function () {
    $diskWriters = collect(Release::STEPS)
        ->filter(fn (array $step): bool => str_starts_with($step[0], 'optimize') || str_ends_with($step[0], ':cache'));

    expect($diskWriters)->toBeEmpty();
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
