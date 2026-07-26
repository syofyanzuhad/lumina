<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Site;
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
