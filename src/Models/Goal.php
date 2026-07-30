<?php

namespace Lumina\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'target_type',
        'target_value',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
