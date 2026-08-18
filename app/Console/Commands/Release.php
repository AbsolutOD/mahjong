<?php

namespace App\Console\Commands;

use Database\Seeders\CardSeeder;
use Illuminate\Console\Command;

/**
 * Everything a deploy has to do to the running application.
 *
 * Laravel Cloud takes a release command as a single line typed into a form,
 * where nothing reviews it and nothing tests it. It calls this instead, so the
 * steps live in the repository next to the code they act on.
 *
 * The seeder is named on purpose. A bare `db:seed` would run DatabaseSeeder,
 * which plants test@example.com — a real account, with a known password, on the
 * public site.
 */
class Release extends Command
{
    protected $signature = 'app:release';

    protected $description = 'Migrate, reseed the card and warm the caches for a new release';

    /**
     * The steps of a release, in the order a deploy must take them.
     *
     * @var list<array{string, array<string, mixed>}>
     */
    public const STEPS = [
        ['migrate', ['--force' => true]],
        ['db:seed', ['--class' => CardSeeder::class, '--force' => true]],
        ['optimize', []],
        ['cache:clear', []],
    ];

    public function handle(): int
    {
        foreach (self::STEPS as [$command, $options]) {
            if ($this->call($command, $options) !== self::SUCCESS) {
                $this->components->error("Release aborted: [{$command}] failed.");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
