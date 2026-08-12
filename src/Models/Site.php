<?php

namespace Lumina\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Lumina\Core\Database\Factories\SiteFactory;

#[Fillable(['domain', 'owner_id', 'is_public', 'share_token', 'share_password', 'api_token'])]
class Site extends Model
{
    use HasFactory;

    protected $table = 'sites';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'share_password',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'has_password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    protected static function newFactory()
    {
        return SiteFactory::new();
    }

    /**
     * Cache-aware domain lookup used by the tracking pipeline.
     *
     * The cached entry is invalidated automatically by the saved/deleted hooks
     * below, so newly created or renamed sites are trackable immediately.
     */
    public static function cachedByDomain(string $domain): ?self
    {
        $domain = Str::lower($domain);

        // Cache only the scalar site id (serializing an Eloquent model into a
        // shared cache store is unreliable), then re-query the fresh model.
        // Note: the 'lumina_site_lookup:' namespace deliberately differs from
        // the 'lumina_site:' rate-limiter keys used by TrackPageview so the two
        // can never collide in the cache store.
        $siteId = Cache::remember('lumina_site_lookup:'.$domain, 3600, function () use ($domain) {
            return static::where('domain', $domain)->value('id');
        });

        return $siteId ? static::find($siteId) : null;
    }

    /**
     * Invalidate the cached domain lookup on any persistence change.
     */
    protected static function booted(): void
    {
        static::saved(function (self $site) {
            Cache::forget('lumina_site_lookup:'.Str::lower($site->domain));
        });

        static::deleted(function (self $site) {
            Cache::forget('lumina_site_lookup:'.Str::lower($site->domain));
        });

        static::updating(function (self $site) {
            if ($site->isDirty('domain')) {
                Cache::forget('lumina_site_lookup:'.Str::lower($site->getOriginal('domain')));
            }
        });
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
     * Get the goals for the site.
     *
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
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

    /**
     * Check if the site has a share password set.
     */
    public function hasSharePassword(): bool
    {
        return ! empty($this->share_password);
    }

    /**
     * Accessor for has_password attribute.
     */
    public function getHasPasswordAttribute(): bool
    {
        return $this->hasSharePassword();
    }

    /**
     * Check if the site is publicly accessible via share token.
     */
    public function isPubliclyAccessible(): bool
    {
        return (bool) $this->is_public && ! empty($this->share_token);
    }

    /**
     * Generate a new 32-character random share token.
     */
    public function generateShareToken(): string
    {
        return Str::random(32);
    }

    /**
     * Generate a new 64-character API token for programmatic access.
     */
    public function generateApiToken(): string
    {
        return 'lum_'.Str::random(60);
    }
}
