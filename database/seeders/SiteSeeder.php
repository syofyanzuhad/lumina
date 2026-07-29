<?php

namespace Database\Seeders;

use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Site::factory()->count(3)->has(Event::factory()->count(10))->create();
    }
}
