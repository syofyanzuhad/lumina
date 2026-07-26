<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Site;
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
