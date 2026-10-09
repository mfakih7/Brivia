<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Idempotent baseline: singleton settings plus DRAFT starter content derived from the approved
 * specification. Creates no users, no passwords, no portfolio projects and no prices.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SingletonSeeder::class,
            DraftContentSeeder::class,
        ]);
    }
}
