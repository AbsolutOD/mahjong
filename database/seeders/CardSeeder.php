<?php

namespace Database\Seeders;

use App\Actions\Cards\ImportCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CardSeeder extends Seeder
{
    public function __construct(private ImportCard $importCard) {}

    /**
     * Seed every card authored under database/cards.
     */
    public function run(): void
    {
        foreach (File::glob(database_path('cards/*.json')) as $path) {
            $this->importCard->handle(
                json_decode(File::get($path), associative: true, flags: JSON_THROW_ON_ERROR)
            );
        }
    }
}
