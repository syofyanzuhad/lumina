<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TrackerScriptSizeTest extends TestCase
{
    public function test_tracker_script_exists_and_is_under_2kb_gzipped(): void
    {
        $scriptPath = __DIR__.'/../../public/js/script.js';

        $this->assertFileExists($scriptPath, 'Compiled tracker script public/js/script.js does not exist.');

        $content = file_get_contents($scriptPath);
        $this->assertNotEmpty($content, 'Compiled tracker script is empty.');

        $gzipped = gzencode($content, 9);
        $gzippedSize = strlen($gzipped);

        $this->assertLessThan(
            2048,
            $gzippedSize,
            "Tracker script gzipped size ({$gzippedSize} bytes) exceeds 2KB (2048 bytes) limit."
        );
    }
}
