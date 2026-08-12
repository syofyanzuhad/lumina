<?php

namespace Lumina\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Lumina\Core\Database\Factories\GoalFactory;

/**
 * @property int $id
 * @property int $site_id
 * @property string $name
 * @property string $target_type
 * @property string $target_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'target_type',
        'target_value',
    ];

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
