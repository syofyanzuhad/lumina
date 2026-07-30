<?php

namespace Lumina\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lumina\Core\Database\Factories\EventFactory;
use Lumina\Core\Enums\DeviceType;

#[Fillable(['site_id', 'path', 'referrer', 'visitor_hash', 'device_type', 'country', 'metadata', 'created_at'])]
class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected static function newFactory()
    {
        return EventFactory::new();
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var string|null
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'device_type' => DeviceType::class,
            'metadata' => 'array',
        ];
    }

    /**
     * Get the site that owns the event.
     *
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
