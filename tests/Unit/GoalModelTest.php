<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lumina\Core\Models\Goal;
use Lumina\Core\Models\Site;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('goal belongs to a site', function () {
    $site = Site::factory()->create();
    $goal = Goal::create([
        'site_id' => $site->id,
        'name' => 'Signup Goal',
        'target_type' => 'path',
        'target_value' => '/thank-you',
    ]);

    $this->assertEquals($site->id, $goal->site->id);
    $this->assertEquals('Signup Goal', $goal->name);
    $this->assertEquals('path', $goal->target_type);
    $this->assertEquals('/thank-you', $goal->target_value);
});
