<?php

namespace Lumina\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;

class InsertEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $siteId,
        public string $path,
        public ?string $referrer,
        public string $visitorHash,
        public DeviceType|string $deviceType,
        public ?string $country = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Event::create([
            'site_id' => $this->siteId,
            'path' => $this->path,
            'referrer' => $this->referrer,
            'visitor_hash' => $this->visitorHash,
            'device_type' => $this->deviceType,
            'country' => $this->country,
            'metadata' => $this->metadata,
        ]);
    }
}
