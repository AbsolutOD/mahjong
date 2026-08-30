<?php

namespace Database\Seeders;

use App\Actions\Cards\ImportCard;
use App\Actions\Cards\LoadCurrentCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CardSeeder extends Seeder
{
    public function __construct(private ImportCard $importCard) {}

    /**
     * Seed every card authored under database/cards.
     *
     * The card is cached forever, so seeding drops the cache itself rather than
     * leaving that to the release command's later `cache:clear`. Two steps that
     * must both run to publish a card can drift apart; one cannot.
     */
    public function run(): void
    {
        foreach (File::glob(database_path('cards/*.json')) as $path) {
            $this->importCard->handle(
                json_decode(File::get($path), associative: true, flags: JSON_THROW_ON_ERROR)
            );
        }

        Cache::forget(LoadCurrentCard::CACHE_KEY);
    }
}
