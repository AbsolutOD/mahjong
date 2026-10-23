<?php

use App\Enums\Phase;
use App\Models\User;

/**
 * The three phases, held in one place (issue #53).
 *
 * The ways in and three navs each wrote the phases out by hand, and had already
 * drifted: the public navbar called the third one "Practice" while everything
 * else said "Practice & Quiz". Each now loops over the enum, so these hold every
 * place a phase is named to the enum's name for it.
 */
test('every place that lists the phases names each one the same way', function (string $template, bool $signedIn) {
    if ($signedIn) {
        $this->actingAs(User::factory()->create());
    }

    $view = $this->blade($template);

    foreach (Phase::cases() as $phase) {
        $view->assertSee($phase->label())
            ->assertSee($phase->url());
    }
})->with([
    'the ways in' => ['<x-ways-in />', false],
    'the public navbar' => ['<x-layouts::public>body</x-layouts::public>', false],
    'the signed-in sidebar' => ['<x-layouts::app.sidebar>body</x-layouts::app.sidebar>', true],
    'the signed-in header' => ['<x-layouts::app.header>body</x-layouts::app.header>', true],
]);

/**
 * The header shell lists the phases twice, once in the desktop navbar and once
 * in the mobile menu, so seeing each phase once would pass with either list
 * gone wrong.
 */
test('the signed-in header lists every phase in both its navbar and its mobile menu', function () {
    $this->actingAs(User::factory()->create());

    $html = (string) $this->blade('<x-layouts::app.header>body</x-layouts::app.header>');

    foreach (Phase::cases() as $phase) {
        expect(substr_count($html, 'href="'.$phase->url().'"'))->toBe(2);
    }
});

/**
 * "Practice & Quiz" crowds the public header on a phone, so below the `sm`
 * breakpoint the navbar falls back to the phase's short name.
 */
test('the public navbar names a phase by its short name on a small screen', function () {
    $this->blade('<x-layouts::public>body</x-layouts::public>')
        ->assertSee('<span class="sm:hidden">'.e(Phase::PracticeAndQuiz->shortLabel()).'</span>', escape: false)
        ->assertSee('<span class="max-sm:hidden">'.e(Phase::PracticeAndQuiz->label()).'</span>', escape: false);
});

test('only the practice phase has a short name', function () {
    expect(Phase::PracticeAndQuiz->shortLabel())->toBe('Practice')
        ->and(Phase::LineDecoder->shortLabel())->toBe(Phase::LineDecoder->label())
        ->and(Phase::HandMatcher->shortLabel())->toBe(Phase::HandMatcher->label());
});

/**
 * A `#[Title]` attribute cannot call into the enum, so each page spells its
 * phase's name out once more; this keeps that spelling honest.
 */
test('each phase\'s page is titled with the phase\'s name', function (Phase $phase) {
    $this->get($phase->url())
        ->assertOk()
        ->assertSee($phase->label().' - '.config('app.name'));
})->with(Phase::cases());
