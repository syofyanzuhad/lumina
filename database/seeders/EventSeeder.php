<?php

namespace Database\Seeders;

use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Site::count() === 0) {
            Site::factory()->create();
        }

        Event::factory()->count(50)->create();
    }
}
