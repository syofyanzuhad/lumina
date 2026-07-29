<?php

namespace Lumina\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Lumina\Core\Database\Factories\SiteFactory;

#[Fillable(['domain', 'owner_id'])]
class Site extends Model
{
    use HasFactory;

    protected $table = 'sites';

    protected static function newFactory()
    {
        return SiteFactory::new();
    }

    /**
     * Get the owner of the site.
     *
     * @return BelongsTo<Model, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', User::class),
            'owner_id'
        );
    }

    /**
     * Get the events for the site.
     *
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Interact with the site's domain.
     */
    protected function domain(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::lower($value),
        );
    }
}
